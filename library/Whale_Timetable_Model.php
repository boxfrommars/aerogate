<?php
/**
 * Модель для сохранения предложений перелётов
 * @author Dmitry Groza (boxfrommars@gmail.com)
 * @TODO стоит написать ленивые геттеры для подготовленных запросов
 * @TODO стоит заменить ? на именованные плейсхолдеры, чтобы не путаться
 */
class Whale_Timetable_Model 
{
	protected $_db;
	
	protected $_getAirportIdPrepared;
	protected $_getAirplaneIdPrepared;
	protected $_getAircompanyIdPrepared;
	
	protected $_getAirportCodePrepared;
	protected $_getAirplaneCodePrepared;
	protected $_getAircompanyCodePrepared;
	protected $_getCityCodePrepared;
	protected $_getCountryCodePrepared;
	
	protected $_insertOfferHeadPrepared;
	protected $_insertOfferPrepared;
	
	// здесь "кэшируем" соответствия имя=>id аэропортов/самолётов/компаний, так как при сохранении 
	// часто нам будут нужны одни и те же id, и глупо каждый раз лезть в базу
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
		
		// наверно, стоит это вынести в "ленивые" геттеры
		$this->_getAirportIdPrepared = $this->_db->prepare('SELECT `id` FROM `airports` WHERE ru_code = ? OR en_code = ?');
		$this->_getAirplaneIdPrepared = $this->_db->prepare('SELECT `id` FROM `airplanes` WHERE ru_code = ? OR en_code = ?');
		$this->_getAircompanyIdPrepared = $this->_db->prepare('SELECT `id` FROM `aircompanies` WHERE ru_code = ? OR en_code = ?');
		
		// для получения кода по id. так как и сирена и галилео понимают en_code, то получаем только их
		$this->_getAirportCodePrepared = $this->_db->prepare('SELECT `en_code` FROM `airports` WHERE `id` = ?');
		$this->_getAirplaneCodePrepared = $this->_db->prepare('SELECT `en_code` FROM `airplanes` WHERE `id` = ?');
		$this->_getAircompanyCodePrepared = $this->_db->prepare('SELECT `en_code` FROM `aircompanies` WHERE `id` = ?');
		$this->_getCityCodePrepared = $this->_db->prepare('SELECT `en_code` FROM `cities` WHERE `id` = ?');
		$this->_getCountryCodePrepared = $this->_db->prepare('SELECT `en_code` FROM `countries` WHERE `id` = ?');
		
		$this->_insertOfferHeadPrepared = $this->_db->prepare('
			INSERT INTO `offers_head` 
				(`USERID`, `ORDERID`, `PRICE`, `OFFERDATE`, `LIVE`, `STATUS`) 
			VALUES 
				(?, ?, ?, NOW(), ?, 1)');
		
		$this->_insertOfferPrepared = $this->_db->prepare('
			INSERT INTO `offers` 
				(`OFFERID`, `PRICE`, `DATEFLY`, `TIMEDEP`, `TIMEARR`, `AIRPORTDEP`, `AIRPORTARR`, `AIRFLY`, `AIRPLANE`, `CLASS`, `FLIGHT_TIME`, `DESCR`, `SEGMENT_NUM`)
			VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
		');
	}
	
	/**
	 * @param array $timetable
	 * @param int $userId id пользователя, подавшего заявку
	 * @param int $orderId id заявки
	 * @param int $live непонятно что
	 */
	public function save($timetable, $userId, $orderId, $live = null) 
	{
		foreach ($timetable as $flight) {
			$this->saveFlight($flight, $userId, $orderId, $live);
		}
	}
	/**
	 * сохраняем предложения перелётов. пользуем транзакции
	 * @param array $flight (перелёт из $timetable)
	 * @param int $userId id пользователя, подавшего заявку
	 * @param int $orderId id заявки
	 * @param int $live непонятно что
	 * @throws Exception 
	 */
	public function saveFlight($flight, $userId, $orderId, $live = null)
	{
		try {
			$this->_db->beginTransaction();
			/* TRANSACTION BEGIN */
				$success = $this->_insertOfferHeadPrepared->execute(array($userId, $orderId, $flight['price'], $live));
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
	
	public function getAirportCode($id)
	{
		$this->_getAirportCodePrepared->execute(array($id));
		return $this->_getAirportCodePrepared->fetchColumn();
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
	
	public function getAirplaneCode($id)
	{
		$this->_getAirplaneCodePrepared->execute(array($id));
		return $this->_getAirplaneCodePrepared->fetchColumn();
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
	
	public function getAircompanyCode($id)
	{
		$this->_getAircompanyCodePrepared->execute(array($id));
		return $this->_getAircompanyCodePrepared->fetchColumn();
	}
	
	// также и для городов
	public function getCityCode($id)
	{
		$this->_getCityCodePrepared->execute(array($id));
		return $this->_getCityCodePrepared->fetchColumn();
	}
	
	public function getCountryCode($id)
	{
		$this->_getCountryCodePrepared->execute(array($id));
		return $this->_getCountryCodePrepared->fetchColumn();
	}
}