#!/opt/local/bin/perl -w

use strict;
use GD;

my $x = 0;
open(F,"<../data/stops.txt");
my $junk = <F>;
while (my $line = <F>) {
	chomp($line);
	# stop_id,stop_code,stop_name,stop_desc,stop_lat,stop_lon,stop_street,stop_city,stop_region,stop_postcode,stop_country,zone_id
	my @line = split(",",$line);
	my $lat = $line[4];
	my $long = $line[5];
	$lat =~ s/\s//g;
	$long =~ s/\s//g;

	$lat = ($lat - 45)*1000;
	$long = ($long + 75)*1000;
}
close(F);
