<?php
	// A helper script for Kathy.

	print implode(',', array(
		'Provider',
		'Description',
		'Longitude',
		'Latitude',
		'Parameter(s)'
	))."\n";
	foreach (json_decode(file_get_contents('../json/obs.177235.json'), TRUE) as $feature) {
		if ($feature['properties']['provider'] == 'Grand River Conservation Authority') {
			print implode(',', array(
				$feature['properties']['provider'],
				$feature['properties']['descr'],
				$feature['geometry']['coordinates'][0],
				$feature['geometry']['coordinates'][1],
				$feature['properties']['timeSeries'] ? '"'.implode(',', array_keys($feature['properties']['timeSeries'])).'"' : ''
			))."\n";
		}
	}
?>
