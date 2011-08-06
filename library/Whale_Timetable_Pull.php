<?php
require_once 'Whale_Timetable_Abstract.php';
require_once 'Whale_Timetable_Siren.php';
require_once 'Whale_Timetable_Galileo.php';

/**
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
	
	
	public function setGateway($name, $options)
	{
		$className = 'Whale_Timetable_'  . ucfirst($name); // если есть аутолоадер, то нужно будет подправить этот метод
		$this->_gateways[$name] = new $className($options);
	}
	
	public function getGateways() 
	{
		return $this->_gateways;
	}
	
	/**
	 * Получаем расписание от каждого из шлюзов, строим общий массив, отдаём
	 * @param unknown_type $params
	 */
	public function getTimetable($params)
	{
		$timetable = array();
		foreach ($this->getGateways() as $gateway) {
			try {
				$timetable = array_merge($timetable, $gateway->getTimetable($params));
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