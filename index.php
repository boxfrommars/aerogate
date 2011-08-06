<?php
define('WHALE_TIMETABLE_DEBUG', 1);
require_once 'library/Whale_Timetable_Pull.php';

 require_once 'config.php';
// $TimetablePull = new Whale_Timetable_Pull($config);

// $query = array(
// 	'city_from' => 'MOW',
// 	'city_to' => 'LED',
// 	'date_to' => '03.10.2011'
// );

// $queryId = 1;
// $timetable = $TimetablePull->getTimetable($query);

// $tstring = file_put_contents('/tmp/timetable.arr', serialize($timetable));

$timetable = unserialize(file_get_contents('/tmp/timetable.arr'));

try {
	$pdo = new PDO(
	    $configDb['db'], 
	    $configDb['user'], 
	    $configDb['password'], 
		array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
	);
} catch (Exception $e) {
	die("DYING: Unable to connect: " . $e->getMessage() . "\n");
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$insertOfferHead = $pdo->prepare('INSERT INTO `offers_head` (`USERID`, `ORDERID`, `PRICE`, `OFFERDATE`, `LIVE`, `STATUS`) VALUES (1, 1, ?, NOW(), NULL, 1)');
$getAirportId = $pdo->prepare('SELECT `id` FROM `airports` WHERE ru_code = ? OR en_code = ?');
$getAirplaneId = $pdo->prepare('SELECT `id` FROM `airplanes` WHERE ru_code = ? OR en_code = ?');
$getAircompanyId = $pdo->prepare('SELECT `id` FROM `aircompanies` WHERE ru_code = ? OR en_code = ?');

$insertOffer = $pdo->prepare('
	INSERT INTO `offers` 
		(`OFFERID`, `PRICE`, `DATEFLY`, `TIMEDEP`, `TIMEARR`, `AIRPORTDEP`, `AIRPORTARR`, `AIRFLY`, `AIRPLANE`, `CLASS`, `FLIGHT_TIME`, `DESCR`, `SEGMENT_NUM`)
	VALUES
		(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
');

foreach ($timetable as $flight) {
	try {
		$pdo->beginTransaction();
		/* TRANSACTION BEGIN */
			$success = $insertOfferHead->execute(array($flight['price']));
			if (!$success) {
				$error = $insertOfferHead->errorInfo();
				throw new Exception('inserting offer head failed with error ' . print_r($error, true));
			} else {
				$offerId = $pdo->lastInsertId();
				foreach ($flight['segments'] as $segment) {
					$getAirportId->execute(array($segment['airportdep'], $segment['airportdep']));
					$airportDepId = $getAirportId->fetchColumn();
						
					$getAirportId->execute(array($segment['airportarr'], $segment['airportarr']));
					$airportArrId = $getAirportId->fetchColumn();
						
					$getAircompanyId->execute(array($segment['airfly'], $segment['airfly']));
					$aircompanyId = $getAircompanyId->fetchColumn();
						
					$getAirplaneId->execute(array($segment['airplane'], $segment['airplane']));
					$airplaneId = $getAirplaneId->fetchColumn();
						
					$success = $insertOffer->execute(array(
						$offerId, $segment['price'], $segment['datefly'], $segment['timedep'], 
						$segment['timearr'], $airportDepId, $airportArrId, $aircompanyId, $airplaneId, 
						$segment['class'], $segment['flight_time'], $segment['descr'], $segment['segment_num']
					));
						
					if (!$success) {
						$error = $insertOffer->errorInfo();
						throw new Exception('inserting offer failed with error ' . print_r($error, true));
					}
					
				}
			}
		/* TRANSACTION END */
		$pdo->commit();
	} catch (Exception $e) {
		$pdo->rollBack();
		echo "Failed: " . $e->getMessage();
	}
}