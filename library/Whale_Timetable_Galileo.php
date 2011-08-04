<?php
class Whale_Timetable_Galileo extends Whale_Timetable_Abstract
{
	protected $_clientId;
	protected $_airClassMap = array(
			'1' => 'E',
			'2' => 'B',
			'3' => 'F',
		);
	
	
	public function __construct($options)
	{
		if (empty($options['client_id'])) {
			throw new Exception('client_id option for Galileo is missing');
		} else {
			$this->_clientId = $options['client_id'];
		}
		parent::__construct($options);
	}
	
	protected function _buildTimetable($result)
	{
//		$result = base64_decode($result);
// 		print 'GALILEO RESULT ' . print_r($result, true) . "\n";
		return array();
	}
	
	protected function _buildData($query) 
	{
		$query = array_merge($this->_defaultQuery, $query);
		
		// 		<pricing>
		// 		*<Currency>EUR</Currency>			валюта оценки (не обяз., по умолч. – RUB)
		// 		<Sort>time</Sort>                       сортировать по времени
		// 		<SortType>DESC</SortType>			сортировать по убыванию
		// 		<Origin>KRR</Origin>				откуда
		// 		<Destination>MOW</Destination>		куда
		// 		<DepDate>23.12.2010</DepDate>      	дата вылета туда
		// 		<ReturnDate>31.12.2010</ReturnDate>     дата вылета обратно
		// 		<Class>E</Class>    				класс
		// 		<AdtNumber>1</AdtNumber>			кол-во взрослых
		// 		<ChdNumber>0</ChdNumber>			кол-во детей (до 12 лет)
		// 		<InfNumber>0</InfNumber>      		кол-во детей (до 2 лет)
		// 		<Through>true</Through>
		// 		<Type>avia</Type>
		// 		</pricing>
		
// 				'city_from' => null,
// 				'city_to' => null,
// 				'date_to' => null,
// 				'date_back' => '',
// 				'air_class' => 1,
// 				'one_flight' => 0,
// 				'adult' => 1,
// 				'child' => 0,
// 				'infant' => 0
		
		
		$xmlData = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><pricing></pricing>');
// 		$xmlData->addChild('Sort', 'time'); 
// 		$xmlData->addChild('SortType', 'ASC'); 
		$xmlData->addChild('Origin', $query['city_from']);
		$xmlData->addChild('Destination', $query['city_to']);
		$xmlData->addChild('DepDate', $query['date_to']);
		$xmlData->addChild('ReturnDate', $query['date_back']);
		$xmlData->addChild('Class', $this->_airClassMap[$query['air_class']]);
		$xmlData->addChild('AdtNumber', $query['adult']);
		$xmlData->addChild('ChdNumber', $query['child']);
		$xmlData->addChild('InfNumber', $query['infant']);
		$xmlData->addChild('Throw', $query['one_flight'] ? 'true' : 'false');
		$xmlData->addChild('Type', 'avia');
		
		$xmlText = $xmlData->asXML();
		print_r($xmlText);
		$xmlTextEncoded = base64_encode($xmlText);
		
		$request = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><request></request>');
		$request->addChild('ClientId', $this->_clientId);
		$request->addChild('XmlText', $xmlTextEncoded);
		
		echo $request->asXML();
	}
}