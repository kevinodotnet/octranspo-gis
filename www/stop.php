<html>
<head>
<title>OCTranspo Dead Simple Bus Updates</title>
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

$appId = '12a97a45';
$apiKey = '1306950a573e79601506d6c1564c4d42';

$devel = $_GET['devel'];
$stop = $_GET['stop'];
$route = $_GET['route'];

if ($stop == "") {	
	include("foot.php");
	return;
}

?>

<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=204783589569220";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
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

<div style="float: left; width: 70px;">&nbsp;</div>
<div class="fb-like" style="float: left;" data-href="http://threebit.net/app/stop.php" data-send="false" data-layout="button_count" data-width="55" data-show-faces="false"></div>
<div style="float: left;"><a  href="https://twitter.com/share" class="twitter-share-button" data-url="http://threebit.net/app/stop.php" data-text="This simple #OCTranspo realtime site works for me" data-via="odonnell_k">Tweet</a></div>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
</div>
<div style="clear: both;">&nbsp;</div>

<?

if ($route != "") {
	getNextStops($route,$stop);
	flush();
	include("foot.php");
	return;
}


# Get routes for the stop
$url = "https://api.octranspo1.com/GetRouteSummaryForStop";

$ch = curl_init();
curl_setopt($ch,CURLOPT_URL,$url);
curl_setopt($ch,CURLOPT_POST,4);
curl_setopt($ch,CURLOPT_POSTFIELDS,"appID=$appId&apiKey=$apiKey&stopNo=$stop");
ob_start();
$curl_ret = curl_exec($ch);
$result = ob_get_contents();
ob_end_clean();
curl_close($ch);

if (preg_match("/<div/",$result)) {
	print "<tr><td colspan=\"3\">";
	print "#failbus!<br/>API error at OCTranspo; try refreshing...";
	print "<!-- XML: ".$result." -->\n";
	print "</td></tr></table>";
	include("foot.php");
	return;
}

if ($devel) { print "<!-- STOP DATA \n\n $result \n\n -->"; }

?>
<?

$xml = simplexml_load_string($result);
$routes = $xml->xpath("//Route/node");

if (sizeof($routes) == 0) {
	$routes = $xml->xpath("//Route");
}

$done = array();
while (list(,$route) = each($routes)) {
	#print "<!-- DONE \n"; print_r($done); print "\n-->\n";
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

function getNextStops($route,$stop) {
	global $appId;
	global $apiKey;

	$url = "https://api.octranspo1.com/GetNextTripsForStop";
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_POST,4);
	curl_setopt($ch,CURLOPT_POSTFIELDS,"appID=$appId&apiKey=$apiKey&routeNo=$route&stopNo=$stop");
	ob_start();
	$curl_ret = curl_exec($ch);
	$result = ob_get_contents();
	ob_end_clean();
	curl_close($ch);

	?>
	<table>
	<tr>
	<td class="h"><a href="?stop=<?= $stop ?>&route=<?= $route ?>">#<?= $route ?></a> to</td>
	<td class="h">In</td>
	<td class="h">As per</td>
	</tr>
	<?
	
	if (preg_match("/<div/",$result)) {
		?>
		<tr>
		<td colspan="3">
		<?
		print "#failbus!<br/>API error at OCTranspo.<br/>try refreshing";
		print "<!-- XML: ".$result." -->\n";
		?>
		</td>
		</tr>
		</table>
		<?
		return;
	}
	
	$xml = simplexml_load_string($result);
	$result = $xml->xpath("//Trip/node");

	$found = 0;
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
	
		?>
		<tr>
		<td><?= $dest ?></td>
		<td><?= $adjTime ?>m</td>
		<td><?= $updated ?></td>
		</tr>
		<?
	}
	if ($found == 0) {
		?>
		<tr>
		<td colspan="3">none at this time</td>
		</tr>
		<?
	}
	?>
	</table>
	<?
}

?>
