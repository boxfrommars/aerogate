<?php
define('WHALE_TIMETABLE_DEBUG', 1);
require_once 'library/Whale_Timetable_Pull.php';
require_once 'library/Whale_Timetable_Model.php';

require_once 'config.php';
$TimetablePull = new Whale_Timetable_Pull($config);
$query = array(
	'city_from' => 'LED',
	'city_to' => 'MOW',
	'date_to' => '03.10.2011',
   	'date_back' => '05.10.2011',
);

$timetable = $TimetablePull->getTimetable($query);

$userId = 1;
$orderId = 1;
$live = null;

$pdo = new PDO($configDb['db'], $configDb['user'], $configDb['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
$TimetableModel = new Whale_Timetable_Model($pdo);
$TimetableModel->save($timetable, $userId, $orderId, $live);