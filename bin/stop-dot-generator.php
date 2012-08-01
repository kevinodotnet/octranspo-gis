<?

$fh = fopen("../data/stops-16.txt","r");
fgets($fh);

$mag = 5;
$h = 450;
$w = 750;

$im = imagecreate($w*$mag,$h*$mag);
$white = imagecolorallocate($im, 255,255,255);
$black = imagecolorallocate($im, 0,0,0);

$minLat = 6000;
$maxLat = -6000;
$minLong = 6000;
$maxLong = -6000;

while (($data = fgetcsv($fh, 1000, ",")) !== false) { 
	$lat = $data[4];
	$long = $data[5];

	$lat = (($lat - 45) * 1000) - 130;
	$long = (($long + 77) * 1000) - 950;

	if ($lat > $maxLat) { $maxLat = $lat; };
	if ($long > $maxLong) { $maxLong = $long; };
	if ($lat < $minLat) { $minLat = $lat; };
	if ($long < $minLong) { $minLong = $long; };

#	$x = $w - $long;
#	$y = $h - $lat;
#

$x = $long;
$y = $lat;

	$x = $x*$mag;
	$y = $y*$mag;

	imagefilledellipse($im,$x,$y,$mag,$mag,$black);

}
fclose($fh);

imagepng($im,"test.png",0);

?>
