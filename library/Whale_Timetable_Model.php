<?php
/**
 * Модель для сохранения предложений перелётов
 * Enter description here ...
 * @author xu
 *
 */
class Whale_Timetable_Model 
{
	protected $_db;
	
	protected $_getAirportIdPrepared;
	protected $_getAirplaneIdPrepared;
	protected $_getAircompanyIdPrepared;
	protected $_insertOfferHeadPrepared;
	protected $_insertOfferPrepared;
	
	protected $_airports = array();
	protected $_airplanes = array();
	protected $_aircompanies = array();
	
	/**
	 * устанавливаем в pdo, также строим нужные подготовленные запросы
	 * @param PDO $db
	 */
	public function __construct($db) 
	{
		$this->_db = $db;
		$this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->_getAirportIdPrepared = $this->_db->prepare('SELECT `id` FROM `airports` WHERE ru_code = ? OR en_code = ?');
		$this->_getAirplaneIdPrepared = $this->_db->prepare('SELECT `id` FROM `airplanes` WHERE ru_code = ? OR en_code = ?');
		$this->_getAircompanyIdPrepared = $this->_db->prepare('SELECT `id` FROM `aircompanies` WHERE ru_code = ? OR en_code = ?');
		
		$this->_insertOfferHeadPrepared = $this->_db->prepare('
			INSERT INTO `offers_head` 
				(`USERID`, `ORDERID`, `PRICE`, `OFFERDATE`, `LIVE`, `STATUS`) 
			VALUES 
				(1, 1, ?, NOW(), NULL, 1)');
		
		$this->_insertOfferPrepared = $this->_db->prepare('
			INSERT INTO `offers` 
				(`OFFERID`, `PRICE`, `DATEFLY`, `TIMEDEP`, `TIMEARR`, `AIRPORTDEP`, `AIRPORTARR`, `AIRFLY`, `AIRPLANE`, `CLASS`, `FLIGHT_TIME`, `DESCR`, `SEGMENT_NUM`)
			VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
		');
	}
	
	public function save($timetable) 
	{
		foreach ($timetable as $flight) {
			$this->saveFlight($flight);
		}
	}
	
	public function saveFlight($flight)
	{
		try {
			$this->_db->beginTransaction();
			/* TRANSACTION BEGIN */
				$success = $this->_insertOfferHeadPrepared->execute(array($flight['price']));
				if (!$success) {
					$error = $this->_insertOfferHeadPrepared->errorInfo();
					throw new Exception('inserting offer head failed with error ' . print_r($error, true));
				} else {
					$offerId = $this->_db->lastInsertId();
					foreach ($flight['segments'] as $segment) {
						$this->_saveSegment($segment, $offerId);
					}
				}
			/* TRANSACTION END */
			$this->_db->commit();
		} catch (Exception $e) {
			$this->_db->rollBack();
			echo "Failed: " . $e->getMessage();
		}
	}
	
	/**
	 * сохраняем сегмент перелёта
	 * @param array $segment массив данных сегмента
	 * @param id $offerId id предложения
	 * @throws Exception
	 */
	protected function _saveSegment($segment, $offerId)
	{
		$success = $this->_insertOfferPrepared->execute(array(
			$offerId, $segment['price'], $segment['datefly'], $segment['timedep'],
			$segment['timearr'], $this->getAirportId($segment['airportdep']), $this->getAirportId($segment['airportarr']), 
			$this->getAircompanyId($segment['airfly']), $this->getAirplaneId($segment['airplane']),
			$segment['class'], $segment['flight_time'], $segment['descr'], $segment['segment_num']
		));
		
		if (!$success) {
			$error = $this->_insertOfferPrepared->errorInfo();
			throw new Exception('inserting offer failed with error ' . print_r($error, true));
		}
		
	}
	
	/**
	 * по имени (неважно, русскому или англ. возвращает id аэропорта),
	 * сначала проверяем, получали ли уже из базы, если да, быстро возвращаем, нет -- смотрим в базе
	 * @param string $name ru_name || en_name
	 */
	public function getAirportId($name)
	{
		if (empty($this->_airports[$name])) {
			$this->_getAirportIdPrepared->execute(array($name, $name));
			$this->_airports[$name] = $this->_getAirportIdPrepared->fetchColumn();
		}
		return $this->_airports[$name];
	}
	
	/**
	 * по имени (неважно, русскому или англ. возвращает id самолёта)
	 * сначала проверяем, получали ли уже из базы, если да, быстро возвращаем, нет -- смотрим в базе
	 * @param string $name ru_name || en_name
	 */
	public function getAirplaneId($name)
	{
		if (empty($this->_airplanes[$name])) {
			$this->_getAirplaneIdPrepared->execute(array($name, $name));
			$this->_airplanes[$name] = $this->_getAirplaneIdPrepared->fetchColumn();
		}
		return $this->_airplanes[$name];
	}
	
	/**
	 * по имени (неважно, русскому или англ. возвращает id авиакомпании)
	 * сначала проверяем, получали ли уже из базы, если да, быстро возвращаем, нет -- смотрим в базе
	 * @param string $name ru_name || en_name
	 */
	public function getAircompanyId($name)
	{
		if (empty($this->_aircompanies[$name])) {
			$this->_getAircompanyIdPrepared->execute(array($name, $name));
			$this->_aircompanies[$name] = $this->_getAircompanyIdPrepared->fetchColumn();
		}
		return $this->_aircompanies[$name];
	}
}