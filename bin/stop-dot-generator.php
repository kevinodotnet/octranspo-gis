<?


$mag = 2;
$h = 450;
$w = 750;

$im = imagecreate($w*$mag,$h*$mag);
$white = imagecolorallocate($im, 255,255,255);
$black = imagecolorallocate($im, 0,0,0);
$red = imagecolorallocate($im, 255,0,0);

$minLat = 6000;
$maxLat = -6000;
$minLong = 6000;
$maxLong = -6000;

$specials = array();
array_push($specials,"BANK");
array_push($specials,"CARLING");
array_push($specials,"ST LAURENT");
array_push($specials,"RIDEAU");
array_push($specials,"CARLING");
array_push($specials,"BASELINE");
array_push($specials,"INNES");
array_push($specials,"NAVAN");
#array_push($specials,"OGILVIE");
array_push($specials,"MONTREAL");
array_push($specials,"ST JOSEPH");

$y = 10; #$h*$mag - 10;
foreach ($specials as $s) {
	imagestring($im,2,10,$y,$s,$red);
	$y = $y + 15;
}

$fh = fopen("../data/stops.txt","r");
fgets($fh);
while (($data = fgetcsv($fh, 1000, ",")) !== false) { 
	$num = $data[1];
	$name = $data[2];
	$lat = $data[4];
	$long = $data[5];

	$colour = $black;

	$size = $mag;
	
	foreach ($specials as $s) {
		if (preg_match("/^$s \//",$name) || preg_match("/\/ $s$/",$name)) {
			$colour = $red;
			$size = $mag*3;
		}
	}


	$lat = (($lat - 45) * 1000) - 130;
	$long = (($long + 77) * 1000) - 950;

	if ($lat > $maxLat) { $maxLat = $lat; };
	if ($long > $maxLong) { $maxLong = $long; };
	if ($lat < $minLat) { $minLat = $lat; };
	if ($long < $minLong) { $minLong = $long; };

	$x = $long;
	$y = $h - $lat;

	#$x = $long;
	#$y = $lat;

	$x = $x*$mag;
	$y = $y*$mag;

	#imagefilledellipse($im,$x,$y,$mag,$mag,$black);
	imagefilledellipse($im,$x,$y-100,$size,$size,$colour);
	#imagestringup($im,1,$x,$y,$num . " " . $name,$black);
	#imagestringup($im,1,$x,$y,$num,$black);

}
fclose($fh);

imagepng($im,"test.png",0);

?>
