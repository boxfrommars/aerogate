<?php
/**
 * Класс для получения рейсов с помощью шлюза "сирена"
 * @author Dmitry Groza (boxfrommars@gmail.com)
 */
class Whale_Timetable_Siren extends Whale_Timetable_Abstract
{
	protected $_password;
	protected $_url;

	public function __construct($options)
	{
		if (empty($options['password'])) {
			throw new Exception('password option for Siren is missed');
		} else {
			$this->_password = $options['password'];
		}
		parent::__construct($options);
	}

	protected function _buildTimetable($result)
	{
		$timetableData = json_decode($result);

		if ($timetableData->error_type == 0) { // ошибок нет
//			print_r($timetableData);
			
			foreach ($timetableData->response as $variant) {
				$segments = array();
				$segmentCounter = 0;
				foreach ($variant->flights as $flight) {
					$segment = array(
						'price' => $flight->total_price,
						'datefly' => $flight->date_dep,
						'timedep' => $flight->time_dep,
						'timearr' => $flight->time_arr,
						'airportdep' => $flight->airport_dep,
						'airportarr' => $flight->airport_arr,
						'airfly' => $flight->airline,
						'airplane' => $flight->airplane,
						'class' => $flight->subclass,
						'flight_time' => $flight->flight_time,
						'descr' => '',
						'segment_num' => $segmentCounter++, 
					);
					$segments[] = $segment;
				}
				
				$summary = array(
					'gateway' => 'siren',
					'price' => $variant->price,
	//				'offerdate' => time(),
	//				'live' => NULL,
	//				'status' => 1,
	//				'user_id' => 1,
	//				'offer_id' => 1,
					'segments' => $segments,
				);
				
				$timetable[] = $summary;
			}
			
			
		} else {
			print_r($timetableData);
			return array();
		}

		return $timetable;
	}

	protected function _buildData($query) {
		$request = array(
			'header' =>  array('request_type' => "get_timetable"),
			'body' => array_merge($this->_defaultQuery, $query),
		);

		$data = array(
			'password' => $this->_password,
			'json' => json_encode($request),
		);
		return $data;
	}

}