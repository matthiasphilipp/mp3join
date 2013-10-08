<?
/*
Script to make your own officium.mp3 file from small mp3 files.
Folders:
/input/ = mp3 files
/output/ = officium.mp3 (joined files)

NEXT VERSION:
[ ]save completorium for other liturgical times (Easter, PassionTide etc.)
[ ]save "prima" (and special resp. br. - Our Lady, Sacred Heart etc.)

KNOWN BUG:
- final mp3 has corrupted mp3 id-tag... (see phpmp3.php)
 */
require("phpmp3.php"); //join mp3 files
require("mp3file.php"); //read mp3 duration/length

////////////////////////////////////////////////////////////////

////////////////////////
// AUXILIAR FUNCTIONS //
////////////////////////
function getFileInfo($filename)
{
    //Make filename friendlier
    $original_filename = $filename;

    $filename = explode("/",$filename); //ignore "input/" from filename
    $filename = $filename[1];
    $filename = explode(".",$filename);

    /*
    mp3 files must respect this pattern: (example: completorium.ant.feria3.normal[.mp3]) (.mp3 is ignored)
    [0] = group (general/completorium/prima/etc.)
    [1]...[n-1] = name
    [n] = speed (normal/fast)
    */

    $group = $filename[0];
    $speed = $filename[count($filename)-2]; //-2 to ignore [.mp3] from filename
    $name = array();
    for($i=1;$i<count($filename)-2;$i++)
    {
        $name []= $filename[$i];
    }
    $name = implode(".",$name);

    $duration = new mp3file($original_filename);
    $duration = $duration->get_metadata();
    if ($duration['Encoding']!='Unknown')
    {
        if ($duration['Length mm:ss'] == "0:00") $duration['Length mm:ss'] = "0:01"; //minimum - approximative
        $duration = "- ".$duration['Length mm:ss'];
    }
    else $duration = '';
    return array('grupo'=>$group,'original_filename'=>$original_filename,'name'=>$name,'speed'=>$speed,'duration'=>$duration);
}
////////////////////////////////////////////////////////////////

if(!empty($_POST['submit'])) //create officium.mp3
{
  $benchmark_start = microtime(true);

  echo "<html><body>";

  if(!empty($_POST['mp3files']))
  {

    $output = 'output/'; //folder
    $output .= 'officium.mp3'; //filename

    //$aux_array = $_POST['mp3files'];
    $aux_array = explode("|",$_POST['all_files']);

    $first_mp3 = array_shift($aux_array);

    $mp3 = new PHPMP3($first_mp3);
    if (count($aux_array) > 0)
    {
        foreach ($aux_array as $path)
        {
            $mp3->striptags();
            $mp3_1 = new PHPMP3($path);
            $mp3->mergeBehind($mp3_1);
            //$mp3->save($newpath);
        }
        //$mp3->multiJoin($first_mp3,$aux_array);
    }

    $mp3->save($output);

    $duration = new mp3file($output);
    $duration = $duration->get_metadata();
    if ($duration['Encoding']!='Unknown')
    {
        if ($duration['Length mm:ss'] == "0:00") $duration['Length mm:ss'] = "0:01"; //minimum - approximative
        $duration = " (duration: ".$duration['Length mm:ss'].")";
    }
    else $duration = '';

    echo "<a href='output/officium.mp3'><img src='images/sound.jpg'> Download officum.mp3$duration</a>";
    echo "<br />";
    echo "<br />";
    echo "List of joined mp3s (in the following order): \n<ol>\n";

    //First mp3 removed
    $info = getFileInfo($first_mp3);
    $name = $info['name'];
    $speed = $info['speed'];
    $duration = $info['duration'];
    echo "\t<li>$name ($speed) $duration</li>\n";

    foreach($aux_array as $one_file) //it is imperative to read in the given order !
    {
        $info = getFileInfo($one_file);
        $name = $info['name'];
        $speed = $info['speed'];
        $duration = $info['duration'];
        echo "\t<li>$name ($speed) $duration</li>\n";
    }

    echo "</ol>\n";

    echo "<br />";
    echo "<br />";

    $benchmark_stop = microtime(true);
    $benchmark_total = $benchmark_stop - $benchmark_start;
    echo "The script took ". $benchmark_total." seconds";

  }
  else
  {
    echo "\n<p>No items selected !</p>";
  }
  echo "\n<p><a href='officium2mp3.php'>Return to main page.</a></p>";
  echo "\n</body>\n</html>";
  exit;

}//POST['submit']

$options = '';
//Read available mp3 from "input" folder
$grupos = array();
foreach (glob("input/*.mp3") as $filename) {

    $info = getFileInfo($filename);
    $group = $info['grupo'];
    $original_filename = $info['original_filename'];
    $name = $info['name'];
    $speed = $info['speed'];
    $duration = $info['duration'];

    if (!array_key_exists($group,$grupos)) $grupos[$group] = '';
    $grupos[$group] .= "\t<option value=\"$original_filename\">$name ($speed) $duration</option>\n";
    //TODO: [ ]order this list/array?
}

foreach($grupos as $grupo=>$values)
{
    $options .= "<optgroup label=\"$grupo\">$values</optgroup>\n";
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />

  <link rel="stylesheet" type="text/css" href="css/jquery.bsmselect.css" />
  <link rel="stylesheet" type="text/css" href="css/example.css" />
  <link rel="stylesheet" type="text/css" href="css/jquery-ui.css" />

  <script type="text/javascript" src="js/jquery-1.7.1.js"></script>
  <script type="text/javascript" src="js/jquery-ui.js"></script>
  <script type="text/javascript" src="js/jquery.bsmselect.js"></script>
  <script type="text/javascript" src="js/jquery.bsmselect.sortable.js"></script>
  <script type="text/javascript" src="js/jquery.bsmselect.compatibility.js"></script>

  <script type="text/javascript">//<![CDATA[

    jQuery(function($) {

      // from example 5 of bsmSelect
      $("#mp3files").bsmSelect({
        showEffect: function($el){ $el.fadeIn(); },
        hideEffect: function($el){ $el.fadeOut(function(){ $(this).remove();}); },
        plugins: [$.bsmSelect.plugins.sortable(), $.bsmSelect.plugins.compatibility()],
        title: 'Pick a mp3 file',
        highlight: 'highlight',
        addItemTarget: 'bottom', //do not use "original" otherwise precompiled script won't work... (items will be out of order)
        removeLabel: '<img src="images/erase.png" title="Remove item" />',
        containerClass: 'bsmContainer',                // Class for container that wraps this widget
        listClass: 'bsmList-custom',                   // Class for the list ($ol)
        listItemClass: 'bsmListItem-custom',           // Class for the <li> list items
        listItemLabelClass: 'bsmListItemLabel-custom', // Class for the label text that appears in list items
        removeClass: 'bsmListItemRemove-custom',       // Class given to the "remove" link
        animate:false, //false=OBLIGATORY : otherwise removing an item will not reflect correctly on "all_files" hidden input. (TODO: [ ]fix this? uptade "all_files" inside a callback function of an animation?)
        extractLabel: function($o) {return $o.parents('optgroup').attr('label') + "&nbsp;>&nbsp;" + $o.html();}
      });

    });
    //]]>

    function searchAndchooseItem(item)
    {
        my_bsm = $("#mp3files").data("bsmSelect");
        item = "input/" + item;

        criancas = $("#bsmSelectbsmContainer0").children();
        for(i in criancas)
        {
            if (criancas[i].tagName)
            {
                if (criancas[i].tagName.toUpperCase() == "OPTION") continue; //ignore 1st option
                optgrp = criancas[i].children;
                for (j in optgrp)
                {
                    if (optgrp[j].tagName)
                    {
                        if (item == optgrp[j].value) //item has been found!
                        {
                            optgrp[j].selected = true; //select
                            temp = $('option:selected:eq(0)', my_bsm.$select); //create bsm object
                            my_bsm.addListItem(temp); //add to bsm (automatically updates "all_files")
                        }
                    }
                    else break;
                }//for j
            }
            else break;
        }//for i
    }

    function loadPreCompiledList(officium_type)
    {
        if (officium_type == 'completorium')
        {
            //RESET
            $("#all_files").get()[0].value = ''; // jQuery style == document.getElementById()
            aux = $("ol").children()[0];
            while (aux != undefined)
            {
                aux.firstChild.click(); //remove/drop item
                aux = $("ol").children()[0];
            }
            //PRESET - example: completorium feria tertia
            searchAndchooseItem('completorium.start.normal.mp3'); //from Jube Domine up to Qui fecit caelum (inclusive)
            searchAndchooseItem('completorium.confiteor.normal.mp3');
            searchAndchooseItem('completorium.misereaturnostri.fast.mp3');
            searchAndchooseItem('completorium.indulgentiam.normal.mp3');
            searchAndchooseItem('completorium.convertenosDeus.normal.mp3');
            searchAndchooseItem('general.deusinadjutorium.normal.mp3');
            searchAndchooseItem('general.gloriapatri.fast.mp3');
            searchAndchooseItem('general.sicuterat.fast.mp3');
            searchAndchooseItem('general.alleluia.normal.mp3');
            searchAndchooseItem('completorium.ant.feria3.normal.mp3');//ant
            searchAndchooseItem('psalmus.11.normal.mp3');//1st ps
            searchAndchooseItem('general.gloriapatri.fast.mp3');
            searchAndchooseItem('general.sicuterat.fast.mp3');
            searchAndchooseItem('psalmus.12.normal.mp3');//2nd ps
            searchAndchooseItem('general.gloriapatri.fast.mp3');
            searchAndchooseItem('general.sicuterat.fast.mp3');
            searchAndchooseItem('psalmus.15.normal.mp3');//3rd ps
            searchAndchooseItem('general.gloriapatri.fast.mp3');
            searchAndchooseItem('general.sicuterat.fast.mp3');
            searchAndchooseItem('completorium.ant.feria3.normal.mp3');//ant
            searchAndchooseItem('completorium.hymn.telucis.fast.mp3');
            searchAndchooseItem('completorium.capitulum.tuauteminnobis.normal.mp3');
            searchAndchooseItem('general.deogratias.fast.mp3');
            searchAndchooseItem('completorium.respbr.inmanustuas.fast.mp3');
            searchAndchooseItem('completorium.versiculi.custodinos.fast.mp3');
            searchAndchooseItem('completorium.ant.salvanos.fast.mp3');
            searchAndchooseItem('completorium.nuncdimittis.normal.mp3');
            searchAndchooseItem('completorium.ant.salvanos.fast.mp3');
            searchAndchooseItem('general.domineexaudi.fast.mp3');
            searchAndchooseItem('general.oremus.normal.mp3');
            searchAndchooseItem('completorium.oratio_visita.fast.mp3');
            searchAndchooseItem('cgeneral.perdominum.fast.mp3');
            searchAndchooseItem('general.domineexaudi.fast.mp3');
            searchAndchooseItem('general.benedicamusdomino.fast.mp3');
            searchAndchooseItem('completorium.benedictio.fast.mp3');
            searchAndchooseItem('completorium.salveregina.normal.mp3');
            searchAndchooseItem('general.orapronobissanctadeigenitrix.normal.mp3');
            searchAndchooseItem('general.oremus.normal.mp3');
            searchAndchooseItem('completorium.oremuspostsalveregina.normal.mp3');
            searchAndchooseItem('general.divinumauxilium.normal.mp3');
        }
    }
    </script>
    <style>
    td
    {
        border: 1px solid black;
        border-collapse: collapse;
        padding: 5px;
    }
    //sticky
    .sticky h2,p{
      font-size:100%;
      font-weight:normal;
    }
    .sticky ul,li{
      list-style:none;
    }
    .sticky ul{
      overflow:hidden;
      padding:3em;
    }
    .sticky ul li a{
      text-decoration:none;
      color:#000;
      background:#ffc;
      display:block;
      width:350px;
      padding:1em;
      -moz-box-shadow:5px 5px 7px rgba(33,33,33,1);
      -webkit-box-shadow: 5px 5px 7px rgba(33,33,33,.7);
      box-shadow: 5px 5px 7px rgba(33,33,33,.7);
      -moz-transition:-moz-transform .15s linear;
      -o-transition:-o-transform .15s linear;
      -webkit-transition:-webkit-transform .15s linear;
    }
    .sticky ul li{
      margin:2em;

    }
    .sticky ul li h2{
      font-size:140%;
      font-weight:bold;
      padding-bottom:5px;
    }
    .sticky ul li p{
      font-family: arial,sans-serif;
      font-size:100%;
    }
    .sticky ul li a{
      -webkit-transform: rotate(-5deg);
      -o-transform: rotate(-5deg);
      -moz-transform:rotate(-5deg);
    }
    .sticky ul li a:hover,ul li a:focus{
      box-shadow:10px 10px 7px rgba(0,0,0,.7);
      -moz-box-shadow:10px 10px 7px rgba(0,0,0,.7);
      -webkit-box-shadow: 10px 10px 7px rgba(0,0,0,.7);
      -webkit-transform: scale(1.25);
      -moz-transform: scale(1.25);
      -o-transform: scale(1.25);
      position:relative;
      z-index:5;
    }
    //sticky
    *{
      margin:0;
      padding:0;
    }
    </style>

    <title>Create your own officium.mp3</title>
</head>
<body>

  <h1>Create your own officium.mp3</h1>
  <h3>Organize the parts of the <i>officium</i> as you see fit</h3>

  <form action="officium2mp3.php" method="post" onsubmit="return confirm('Are you sure?');">

    <label for="mp3files">Select the mp3 files from the list below :</label>
    <select id="mp3files" multiple="multiple" name="mp3files[]">
        <?=$options?>
    </select>
    <br />
    <input type='hidden' value='' name='all_files' id='all_files' />

    <p style="clear:both;"><input class='btn' type="submit" name="submit" value="Create officium.mp3" /></p>

    <p style="clear:both;"><input type="button" onclick="loadPreCompiledList('completorium')" value="Generate structure of COMPLETORIUM" /></p>

  </form>

<div class="sticky">
  <ul>
    <li>
      <a href="#">
        <h2>Officium "cheat sheet"</h2>
        <p>
            <table style='border-collapse: collapse;;border:1px solid blue;'>
                <tr>
                    <th>Officium</th>
                    <th>Dies</th>
                    <th>Psalmi (in ordine)</th>
                </tr>
                <tr>
                    <td>Completorium</td>
                    <td>Dominica</td>
                    <td>4,90,133</td>
                </tr>
                <tr>
                    <td>Completorium</td>
                    <td>Feria 2</td>
                    <td>6,7i,7ii</td>
                </tr>
                <tr>
                    <td>Completorium</td>
                    <td>Feria 3</td>
                    <td>11,12,15</td>
                </tr>
                <tr>
                    <td>Completorium</td>
                    <td>Feria 4</td>
                    <td>33i,33ii,60</td>
                </tr>
                <tr>
                    <td>Completorium</td>
                    <td>Feria 5</td>
                    <td>69,70i,70ii</td>
                </tr>
                <tr>
                    <td>Completorium</td>
                    <td>Feria 6</td>
                    <td>76i,76ii,85</td>
                </tr>
                <tr>
                    <td>Completorium</td>
                    <td>Sabbato</td>
                    <td>87,102i,102ii</td>
                </tr>
            </table>
        </p>
      </a>
    </li>
  </ul>


</div> <!-- end of sticky class -->

</body>
</html>
