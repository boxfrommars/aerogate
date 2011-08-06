<?php
// galileo

$xmlData = file_get_contents('/tmp/galileo.xml');
$xml = new SimpleXMLElement($xmlData);

$timetable = array();

foreach ($xml->Variant as $variant) {
	$segments = array();
	
	
	
	/*
	  `OFFERID` mediumint(8) внешний ключ для связи с offers_head,
	  `PRICE` decimal(8,2) цена каждого сегмента,
	  `DATEFLY` date дата вылета,
	  `TIMEDEP` time время вылета,
	  `TIMEARR` time время прилета,
	  `AIRPORTDEP` аэропорт вылета,
	  `AIRPORTARR` mediumint(9) аэропорт прилета,
	  `AIRFLY` mediumint(8) авиакомпания-перевозчик,
	  `AIRPLANE` mediumint(9) самолет,
	  `CLASS` varchar(4) не помню уже, что такое, подставь что-нибудь,
	  `FLIGHT_TIME` time время в пути,
	  `DESCR` text если вдруг какое примечание будет,
	  `SEGMENT_NUM` smallint(5) порядок сегментов, т.е. в том порядке, в каком поулчаешь предложения из систем бронирования, так по порядку потом и записываешь. 1, 2 и т.д. Это чисто мне потом для удобства.
	*/
	
	$segmentCounter = 0;
	foreach ($variant->FlightsTo->Flight as $xmlSegment) {
		$segment = array(
			'price' => NULL,
			'datefly' => date('Y-m-d', strtotime($xmlSegment->DeptDate)),
			'timedep' => date('h:i:s', strtotime($xmlSegment->DeptDate)),
			'timearr' => date('h:i:s', strtotime($xmlSegment->ArrvDate)),
			'airportdep' => (string) $xmlSegment->Origin,
			'airportarr' => (string) $xmlSegment->Destination,
			'airfly' => (string) $xmlSegment->Company,
			'airplane' => (string) $xmlSegment->Airplane,
			'class' => (string) $xmlSegment->Class,
			'flight_time' => (string) $xmlSegment->FlightTime,
			'descr' => '',
			'segment_num' => $segmentCounter++, 
		);
		$segments[] = $segment;
	}
	
	$summary = array(
		'price' => (string) $variant->TotalPrice,
		'offerdate' => time(),
		'live' => NULL,
		'status' => 1,
		'user_id' => 1,
		'offer_id' => 1,
		'segments' => $segments,
	);
	
	$timetable[] = $summary;
}

print_r($timetable);