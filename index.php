<?php
define('WHALE_TIMETABLE_DEBUG', 1);
require_once 'library/Whale_Timetable_Pull.php';

require_once 'config.php';
$TimetablePull = new Whale_Timetable_Pull($config);

$query = array(
	'city_from' => 'KRR',
	'city_to' => 'VCE',
	'date_to' => '03.10.2011'
);

$queryId = 1;
$timetable = $TimetablePull->getTimetable($query);

print_r($timetable);