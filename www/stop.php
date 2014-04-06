<?

$appId = 'CHANGE_ME';
$apiKey = 'CHANGE_ME';

//
// Good luck with your coding! 
//
// You'll need to get your own appID and apiKey though 
// from http://www.octranspo1.com/developers/register
//
// Copyright Kevin O'Donnell, but I hereby license you
// to do whatever the heck you want with the code for
// any reason, blah blah blah, including removing this
// copyright notice.
//
// By the way, get interested in Copyright issues
// by reading anything on http://www.michaelgeist.ca/
//
// kevino@kevino.net
//

$devel = $_GET['devel'];
$stop = $_GET['stop'];
$reload = $_GET['reload'];
if ($reload == '') { $reload = 0; }
$routes = explode(" ",$_GET['route']);
$route = $_GET['route'];

?>
<html>
<head>
<?
if ($stop != '') {
	if ($route != '') {
		?>
		<title><?= $stop.': '.$route ?> OCTranspo Dead Simple Bus Updates</title>
		<?
	} else {
		?>
		<title><?= $stop ?>: OCTranspo Dead Simple Bus Updates</title>
		<?
	}
} else {
	?>
	<title>OCTranspo Dead Simple Bus Updates</title>
	<?
}
?>
<meta name="viewport" content="width=320; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;"/>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
body, td {
	font-family: Verdana;
	font-size: 10pt;
}
input {
	width: 75px;
}
table {
	border-top: solid 1px #c0c0c0;
	border-collapse: collapse;
	width: 300px;
}
td {
	border-bottom: solid 1px #c0c0c0;
	border-right: solid 1px #c0c0c0;
	border-left: solid 1px #c0c0c0;
	padding: 2px;
	text-align: center;
	width: 100px;
}
.h {
	background: #f0f0f0;
	font-weight: bold;
}
</style>
</head>
<body>

<?


if ($stop == "") {	
	include("foot.php");
	return;
}

?>

<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-6324294-4']);
  _gaq.push(['_trackPageview']);
  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>

<div style="width: 300px; text-align: center;">

<?php

if ($route != "") {
	foreach ($routes as $r) {
		getNextStops($r,$stop,1);
	}
	flush();
	include("foot.php");
	return;
}

# Get routes for the stop

#	$url = "https://api.octranspo1.com/GetRouteSummaryForStop/ocapi";
$url = "http://api.octranspo1.com/v1.1/GetRouteSummaryForStop";

$ch = curl_init();
curl_setopt($ch,CURLOPT_URL,$url);
curl_setopt($ch,CURLOPT_POST,4);
curl_setopt($ch,CURLOPT_POSTFIELDS,"appID=$appId&apiKey=$apiKey&stopNo=$stop");
ob_start();
$curl_ret = curl_exec($ch);
$result = ob_get_contents();
ob_end_clean();
curl_close($ch);

$result = preg_replace("/soap:/","",$result);
$result = preg_replace('/ xmlns:[^"]+"[^"]+"/',"",$result);
$result = preg_replace('/ xmlns="[^"]+"/',"",$result);

if (preg_match("/<div/",$result)) {
	print "<tr><td colspan=\"3\">";
	print "#failbus!<br/>API error at OCTranspo; try refreshing...";
	print "<!-- XML: ".$result." -->\n";
	print "</td></tr></table>";
	include("foot.php");
	return;
}

if ($devel) { print "<!-- STOP DATA \n\n $result \n\n -->\n"; }

$xml = simplexml_load_string($result);
$xml->registerXPathNamespace("soap", "http://schemas.xmlsoap.org/soap/envelope/");
$xml->registerXPathNamespace("oc", "http://octranspo.com");
if ($devel) { print "<!--  xml: \n\n"; print print_r($xml); print "--> \n\n"; }
# print "<!--\n\n XML: \n\n"; print print_r($xml); print "--> \n\n";
$routes = $xml->xpath("//Route");
if ($devel) { print "<!--\n\n routes: \n\n"; print print_r($routes); print "--> \n\n"; }

$done = array();
while (list(,$route) = each($routes)) {
	foreach ($route->children() as $child) {
		if ($child->getName() == 'RouteNo') { $routeNo = $child; }
		if ($child->getName() == 'RouteHeading') { $dest = $child; }
	}
	if ($done["".$routeNo] == 1) {
		continue;
	}
	getNextStops($routeNo,$stop);
	$done["".$routeNo] = 1;
	flush();
}
#print "<!-- DONE \n"; print_r($done); print "\n-->\n";

include("foot.php");
return;

function renderReload() {
	global $stop;
	global $route;
	global $reload;
	if ($reload <= 10) {
	?>
	<div id="timer" style="font-size: 80%;"></div>
	<script>
	var index = 90;
	function rr() {
		if (index-- <= 0) {
			document.getElementById('timer').innerHTML = 'Reloading... ';
			window.location = 'http://threebit.net/app/stop.php?stop=<?= $stop ?>&route=<?= $route ?>&reload=<?= $reload ?>';
		} else {
			document.getElementById('timer').innerHTML = 'Auto reload in ' + index + ' seconds...';
		}
	}
	setInterval(function(){rr()},1000);
	</script>
	<?
	} else {
	?>
	<div id="timer" style="font-size: 80%;">No more reloads (max of 20)</div>
	<?
	}
}

function getNextStops($route,$stop,$sayNotRunning=0) {
	global $appId;
	global $apiKey;
	global $devel;

	$url = "https://api.octranspo1.com/GetNextTripsForStop";
	$url = "http://api.octranspo1.com/v1.1/GetNextTripsForStop";
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_POST,4);
	curl_setopt($ch,CURLOPT_POSTFIELDS,"appID=$appId&apiKey=$apiKey&routeNo=$route&stopNo=$stop");
	ob_start();
	$curl_ret = curl_exec($ch);
	$result = ob_get_contents();
	ob_end_clean();
	curl_close($ch);

	$result = preg_replace("/soap:/","",$result);
	$result = preg_replace('/ xmlns:[^"]+"[^"]+"/',"",$result);
	$result = preg_replace('/ xmlns="[^"]+"/',"",$result);
	if ($devel) {
		print "<!-- STOP $stop and ROUTE $route RESULT\n $result \n -->\n";
	}

	if (preg_match("/<div/",$result)) {
		?>
		<?
		print "#failbus!<br/>API error at OCTranspo.<br/>try refreshing";
		print "<!-- XML: ".$result." -->\n";
		?>
		<?
		return;
	}

	$xml = simplexml_load_string($result);
	# print "<!-- print_r() \n\n "; print print_r($xml); print " -->\n";
	$result = $xml->xpath("//Trip");

	$found = 0;
	$first = 1;
	while (list(,$trip) = each($result)) {
		$found = 1;
		$dest = "";
		$lat = "";
		$long = "";
		$adjTime = "";
		$adjAge = "";
		$startTime = "";
		foreach ($trip->children() as $child) {
			if ($child->getName() == 'TripDestination') { $dest =  $child; }
			if ($child->getName() == 'Latitude') { $lat =  $child; }
			if ($child->getName() == 'Longitude') { $long =  $child; }
			if ($child->getName() == 'AdjustedScheduleTime') { $adjTime =  $child; }
			if ($child->getName() == 'AdjustmentAge') { $adjAge =  $child; }
			if ($child->getName() == 'TripStartTime') { $startTime =  $child; }
		}
	
		$updated = intval($adjAge)."m ago";
		if ($adjAge < 0) {
			$updated = "schedule";
		} else if ($adjAge < 1) {
			$secs = intval(((double) $adjAge) * 60);
			$updated = $secs."s ago";
		}

		if ($first) {
	
		?>
	<table>
	<tr>
	<td class="h"><a href="?stop=<?= $stop ?>&route=<?= $route ?>">#<?= $route ?></a> to</td>
	<td class="h">In</td>
	<td class="h">As per</td>
	</tr>
	<?
	}
	$first = 0;
	?>
		<tr>
		<td><?= $dest ?></td>
		<td><?= $adjTime ?>m</td>
		<td><?= $updated ?></td>
		</tr>
		<?
	}
	if ($found == 0) {
		if ($sayNotRunning) {
		?>
		<table>
		<tr>
		<td class="h"><?= $route ?> - not running</td>
		</tr>
		</table>
		<?
		}
	} else {
		?>
		</table>
		<?
	}
}

?>

