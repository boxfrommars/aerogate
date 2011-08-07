<?php
require_once 'Whale_Timetable_Abstract.php';
require_once 'Whale_Timetable_Siren.php';
require_once 'Whale_Timetable_Galileo.php';

/**
 * пул для шлюзов
 * @author Dmitry Groza (boxfrommars@gmail.com)
 */
class Whale_Timetable_Pull
{
    
	protected $_gateways;
	
	/**
	 * 
	 * получает массив вида:
	 * array(
	 * 		'gateways' => array(
	 * 			'siren' => (array) $sirenOptions,
	 * 			'galileo' => (array) $galileoOptions
	 * 		)
	 * )
	 * 
	 * @param array $options
	 */
	public function __construct($options) 
	{
		// удостоверимся, что правильный формат настроек шлюзов
		if (!empty($options['gateways']) && is_array($options['gateways'])) {
			foreach($options['gateways'] as $gatewayName => $gatewayOptions) {
				$this->setGateway($gatewayName, $gatewayOptions);
			}
		} else {
			throw new Exception('Invalid options for gateways');
		}
	}
	
	/**
	 * добавляем шлюз 
	 * @param string $name имя шлюза
	 * @param array $options опции шлюза
	 */
	public function setGateway($name, $options)
	{
		$className = 'Whale_Timetable_'  . ucfirst($name); // если есть аутолоадер, то нужно будет подправить этот метод
		$this->_gateways[$name] = new $className($options);
	}
	
	/**
	 * Получаем расписание от каждого из шлюзов, строим общий массив, отдаём. вид массива см. в документации:
	 * @param unknown_type $query см. Whale_Timetable_Abstract::$_defaultQuery
	 * @return array $timetable
	 */
	public function getTimetable($query)
	{
		$timetable = array();
		foreach ($this->_gateways as $gateway) {
			try {
				$timetable = array_merge($timetable, $gateway->getTimetable($query));
			} catch (Exception $e) {
				if (defined('WHALE_TIMETABLE_DEBUG') && WHALE_TIMETABLE_DEBUG) {
					print $e->getMessage() . "\n";
					print $e->getTraceAsString() . "\n";
				}
			}
			
		}
		return $timetable;
	}
}