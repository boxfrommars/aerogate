<?php
abstract class Whale_Timetable_Abstract implements Whale_Timetable_Interface 
{
	protected $_url;
	
	/**
	 * city_from — код города отправления.
	 * city_to — код города назначения.
	 * date_to — дата отправления (формат dd.mm.yyyy).
	 * date_back — дата возвращения (пустая строка, если перелёт в одну сторону).
	 * air_class — класс обслуживания (1 — эконом, 2 — бизнес, 3 — первый).
	 * one_flight — только прямые рейсы (1 — только прямые, 0 — все рейсы).
	 * adult — количество пассажиров старше 14 лет.
	 * child — количество детей в возрасте от 2 до 14 лет.
	 * infant — количество младенцев в возрасте от 0 до 2 лет.
	 */
	protected $_defaultQuery = array(
		'city_from' => null,
		'city_to' => null,
		'date_to' => null,
		'date_back' => '',
		'air_class' => 1,
		'one_flight' => 0,
		'adult' => 1,
		'child' => 0,
		'infant' => 0
	);
	
	public function __construct($options)
	{
		if (!empty($options['url'])) {
			$this->_url = $options['url'];
		} else {
			throw new Exception('url option missing');
		}
	}
	
	public function getTimetable($query)
	{
		$data = $this->_buildData($query);
		$result = $this->_sendRequest($data, $this->_url);
		$timetable = $this->_buildTimetable($result);
		return $timetable;
	}
	
	abstract protected function _buildData($query);
	abstract protected function _buildTimetable($query);
	
	/**
	 * 
	 * отправляем массив данных $data на урл $url
	 * @param array $data
	 * @param string $url
	 * @throws Exception
	 * @return mixed
	 */
	protected function _sendRequest($data, $url) 
	{
		$resource = curl_init($url);
		curl_setopt($resource, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($resource, CURLOPT_POST, 1);
		curl_setopt($resource, CURLOPT_POSTFIELDS, $data);
		
		curl_setopt($resource, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($resource, CURLOPT_SSL_VERIFYHOST, FALSE);
// 		curl_setopt($resource, CURLOPT_CAINFO, "cacert.pem");
		
		$result = null;
		$result = curl_exec($resource);
		
		// DEBUG BEGIN
		if (defined('WHALE_TIMETABLE_DEBUG') && WHALE_TIMETABLE_DEBUG) {
// 			print_r(curl_getinfo($resource));
			print "REQUEST: \n";
 			print_r($data);
			print "RESULT : \n";
			print $result . "\n";
		}
		// DEBUG END
		
		$error_code = curl_errno($resource);
		if ($error_code != 0) {
			$error = curl_error($resource);
			curl_close($resource);	
			throw new Exception("cURL error (code {$error_code}): {$error}\n");
		}
		
		curl_close($resource);		
		return $result;
	}
}