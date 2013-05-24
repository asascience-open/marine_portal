<?php
  include_once('../../config/'.getenv('config').'.php');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <style>
      html, body {
   	height: 100%;
   	margin: 0 0 6px;
   	padding: 0;
      }
    </style>
    <meta charset="utf-8">
    <meta name="generator" content="CoffeeCup HTML Editor (www.coffeecup.com)">
    <meta name="dcterms.created" content="Fri, 15 Mar 2013 17:57:07 GMT">
    <meta name="description" content="Help file to support GLOS Data Portal Beta">
    <meta name="keywords" content="help glos">
    <link href="../../css/<?php echo getenv('config')?>.css" rel="stylesheet" type="text/css"></link>
    <title><?php echo $title?> Help</title>
	<link type="text/css" rel="stylesheet" href="style_eHelp.css">
    <!--[if IE]>
    <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
  </head>
<body>
<?php echo $bannerHtml?>
  <table>
<tr colspan="2">
	<td>
	<div class="main_head"><?php echo $title?> Help</div>
	<p></p>
	</td>
</tr>
<tr colspan="2">
	<td>Choose the topic you would like more information on: <i>(ctrl + click on link to jump to that topic)</i>
	</td>
</tr>
<tr>
	<td>
	<ul>
		<li><a href="#GI">General Information</a></li>
		<li><a href="#PO">Point Observations</a></li>
		<li><a href="#SO">Satellite Observations</a></li>
		<li><a href="#MF">Model Forecasts</a></li>
		<li><a href="#SRCH">Searching</a></li>
		<li><a href="#PRNT">Printing and Other Options</a></li>
	</ul>
	</td>
</tr>
<tr colspan="2">
	<td><a class="sub_head" name="PO">General Information:</a>
	<p class="med"></p>
	</td>
</tr>
<tr>
	<td><img class="imgDisplay" width=600 src='de_main.png'>
	</td>
</tr>
<tr colspan="2">
	<td>The GLOS Observations Explorer is divided into two parts.  The left side of the screen offers a map which varies depending on which tab, feature, or option you have chosen. The right side of the screen provides you with various options broken into three areas (tabs): <b>Point Observations -- Satellite Observations -- Model Forecasts.</b>
	<p></p>
	<hr>
	<p class="large"></p>
	</td>
</tr>
<tr>
	<td><a class="sub_head" name="PO">Point Observations:</a>
	<p class="med"></p>
	</td>
</tr>
<tr colspan="2">
	<td><img class="imgDisplayPlain" width="600" src='de_poTab2.png'>	
	<p class="large"></p>
	</td>
</tr>
<tr colspan="2">
	<td>The <b>Point Observations tab</b> gives you the capability to view data either by choosing a station on the map or by narrowing your selection to the name of a specific station. Stations are depicted by dots on the map. Zooming in on the map will allow you to view more stations.
	</td>
</tr>
<tr colspan="2">
	<td><p class="large"></p>
	I. Choosing a station from the map:
	</td>
</tr>
<tr>
	<td><img class="imgDisplay" width=350 src='de_poStation.png'><p>If you hover (mouse-over) a station a pop-up will appear where you can view the most recent observation.  A gray versus a colored dot indicates there are no reported observations for that station.</p>
	</td>
</tr>
<tr>
	<td><img class="imgDisplay" width="350" src='de_poOther.png'><p>The information will change depending on what option you have selected on the right side of the page (winds, waves, water temp, or water level). You can also choose "Other observations" from a dropdown list (air temperature, dissolved oxygen, streamflow, turbidity).</p>
<p>An informational legend appears below your selection depending on your choice of data. </p>
  	</td>
</tr>
<tr>
	<td><img class="imgDisplay" width=350 src='de_poExtLink.png'><p>If you click on (instead of hover over) a station's dot, you will see more detailed information including a higher specificity of data depending on what observations are available.</p>
<p>Clicking on the station information link will take you to the homepage for that station. </p>
  	</td>
</tr>
<tr colspan="2">
	<td><p class="large"></p>
	II. Choosing a station by selections from the dropdown list:
	</td> 
</tr>
<tr>
	<td><img class="imgDisplay" width=600 src='de_poStationName.png'></td>
<tr>
	<td>You also have the option to choose a specific station name.  Scroll through the folders on the right side of the screen. Clicking the + sign will expand that folder. Find the station you are interested in and double-click.  The map will now show your station (a highlighted and larger dot) and you will be able to hover as noted above or select for more information.
  </td>
</tr>
<tr>
    <td colspan=2>
	<hr>
  	</td>
</tr>
<tr>
	<td><a class="sub_head" name="SO">Satellite Observations:</a>
	<p class="med"></p>
	</td>
</tr>
<tr colspan="2">
	<td><img class="imgDisplayPlain" width="600" src='de_soMain.png'>	
	<p class="large"></p>
	</td>
</tr>
<tr colspan="2">
	<td>The <b>Satellite Observations tab</b> gives you the ability to view either the water surface temperature or the radar base reflectivity for the region. Make your selection by using the Type dropdown. You can change the opacity/contrast by moving the available slider bar.  In addition, you can choose to view any current weather advisories, watches or warnings by selecting 'yes' to view weather hazards.
	</td>
</tr>
<tr>
    <td colspan=2>
	<hr>
  	</td>
</tr>
<tr>
	<td><a class="sub_head" name="MF">Model Forecasts:</a>
	<p class="med"></p>
	</td>
</tr>
<tr colspan="2">
	<td><img class="imgDisplayPlain" width="600" src='de_mfMain.png'>	
	<p class="large"></p>
	</td>
</tr>
<tr colspan="2">
	<td>Use the <b>Model Forecasts tab</b> to view forecasts based either on the current time or a future time range. You also can change the opacity/contrast of your map by using the provided slider.
	</td>
</tr>
<tr>
	<td><img class="imgDisplay" width=350 src='de_mfType.png'><p>Choose the TYPE of model from the dropdown on the right side of the screen (Currents GLERL; Currents NOS; Ice Thickness GLERL; Water level GLERL; Waves GLERL), then click on a point on the map. Data will appear in the box under the map.</p>
  </td>
</tr>
<tr>
	<td><img class="imgDisplay" width=350 src='de_mfSlider.png'><p>Change the Time slider to look forward in time.</p>
  </td>
</tr>
<tr>
    <td colspan=2>
	<hr>
  	</td>
</tr>
<tr>
	<td><a class="sub_head" name="SRCH">Searching:</a>
	<p class="med"></p>
	</td>
</tr> 
<tr colspan="2">
	<td><img class="imgDisplayPlain" src='de_srchMain.png'>	
	<p class="large"></p>
	</td>
</tr>
<tr colspan="2">
	<td>You can enter <b>keywords (parameters of interest) into the search box</b> to get to specific data or stations. You can search for observations, models, or archived data as well as specific station names or locations. These may be further refined by geography or time window.
	</td>
</tr> 
<tr colspan="2">
	<td><img class="imgDisplayPlain" src='de_srchResults.png'>	
	<p class="large"></p>
	</td>
</tr>
<tr colspan="2">
	<td>To specify more information to narrow your search either choose an observation to preview from a list (when applicable) provided at the bottom of the observation, select an 'area of interest filter', or select a time filter at the top of the pop-up.</td>
</tr> 
<tr colspan="2">
	<td><img class="imgDisplayPlain" src='de_srchFilter.png'>	
	<p class="large"></p>
	</td>
</tr>
<tr>
	<td><a class="sub_head" name="PRNT">Other Options:</a>
	<p class="med"></p>
	</td>
</tr> 
<tr>
	<td>a. <b>Printing</b> :  To get a printer-friendly page of your observation, click the print icon at the top right of the Portal.<img class="imgDisplaySmall" src='de_otherPrinter.png'>
	</td>
</tr> 
<tr>
	<td>b. <b>Linking</b>: If you want to create a bookmark or send a link of your Observation, click the link icon.<img class="imgDisplaySmall" src='de_otherLink.png'>
	</td>
</tr> 
<tr colspan="2">
	<td>c. <b>Help</b>: Click the help icon to get to this page.<img class="imgDisplaySmall" src='de_otherHelp.png'>
	</td>
</tr> 
<tr colspan="2">
    <td>e. <b>Map Display</b>
	</td>
</tr>
<tr colspan="2">
	<td>You can change the background of your map by choosing the Background dropdown at the top of the map.  The default display is "ESRI Ocean", but you may choose from CloudMade, Google Hybrid, Google Satellite, Google Terrain, or OpenStreetMap.  You can also zoom into the map using your mouse scroll functionality, choosing the '+/-' options to the left of the map, or the magnifying glass. You can reset that zoom by clicking the Reset zoom icon.
	</td>
</tr> 
<tr colspan="2">
	<td><img class="imgDisplay" src='de_otherMap.png'>	
	<p class="large"></p>
	</td>
</tr>
<tr colspan="2">
    <td>d. <b>Map Messages</b> will also be provided when appropriate and appear to the top left of the map display.
	</td>
</tr>
<tr colspan="2">
	<td><img class="imgDisplay" src='de_otherMessages.png'>	
	<p class="large"></p>
	</td>
</tr>
<tr>  
    <td colspan=2>
	<hr>
  	</td>
</tr>
</table>
</body>
</html>
