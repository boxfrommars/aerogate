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
        $result = json_decode($result);
        return array();
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