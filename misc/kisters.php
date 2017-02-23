<?php

	/*
		This little helper script produces what getObsLocations.php needs for
		Grand River Conservation Authority's kisters API.  It relies on a CSV
		they gave to us listing the stations, ids, and parameters.  Blech, eh?
		The output of this file is a PHP structure that needs to get pumped into
		the 'stations' array inside getObsLocations.php.
	*/

	$param = array(
		'HG' => array(
			'name' => 'RiverStage',
			'uom' => 'm'
		),
		'PA' => array(
			'name' => 'AtmosphericPressure',
			'uom' => 'Kpa'
		),
		'PN' => array(
			'name' => 'Precipitation',
			'uom' => 'mm'
		),
		'QR' => array(
			'name' => 'Streamflow',
			'uom' => 'cumec'
		),
		'TA' => array(
			'name' => 'AirTemperature',
			'uom' => 'C'
		),
		'UD' => array(
			'name' => 'WindDirection',
			'uom' => 'deg'
		),
		'US' => array(
			'name' => 'WindSpeed',
			'uom' => 'km/h'
		),
		'WO' => array(
			'name' => 'Dissolved Oxygen',
			'uom' => 'mg/L'
		),
		'WP' => array(
			'name' => 'pH',
			'uom' => 'standard units'
		)
	);

	$data = array();
	foreach (csv_to_array(file_get_contents('kisters.csv')) as $a) {
		if (!array_key_exists('S-'.$a['station_no'], $data)) {
			$data['S-'.$a['station_no']] = array();
		}
		array_push(
			$data['S-'.$a['station_no']],
			array(
				'station_no' => $a['station_no'],
				'id' => $a['ts_id'],
				'name' => $param[$a['parametertype_name']]['name'],
				'uom' => $param[$a['parametertype_name']]['uom']
			)
		);
	}

	$i = 0;
	foreach ($data as $station) {
		printf("        '%s' => array(", $station[0]['station_no']);
		$j = 0;
		foreach ($station as $d) {
			printf("array('id' => '%s', 'name' => '%s', 'uom' => '%s')", $d['id'], $d['name'], $d['uom']);
			if ($j++ < count($station) - 1) {
				print ', ';
			}
		}
		print ')';
		if ($i++ < count($data) - 1) {
			print ', ';
		}
		print "\n";
	}

// from http://www.php.net/manual/en/function.str-getcsv.php#104558
function csv_to_array($input,$delimiter=',') {
  $header  = null;
  $data    = array();
  $csvData = str_getcsv($input,"\n");
  foreach ($csvData as $csvLine) {
    if (is_null($header)) {
      $header = explode($delimiter, $csvLine);
    }
    else {
      $items = explode($delimiter, $csvLine);
      for ($n = 0,$m = count($header); $n < $m; $n++) {
        $prepareData[$header[$n]] = $items[$n];
      }
      $data[] = $prepareData;
    }
  }
  return $data;
}
?>
