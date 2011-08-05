<?php
/**
 * Класс для получения рейсов с помощью шлюза Galileo
 * 
 * @author xu
 *
 */
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
	
	/**
	 * на основе ответа стоим список рейсов
	 * @see Whale_Timetable_Abstract::_buildTimetable()
	 */
	protected function _buildTimetable($result)
	{
		$resultEncoded = base64_decode($result);
		$key = openssl_get_privatekey('file:///home/xu/workspace/aerogate/ClientKeyPrivate.pem');
		$xmlTextDecrypted = '';
		$isDecrypted = openssl_private_decrypt($resultEncoded, $xmlTextDecrypted, $key);
		
		print 'GALILEO RESULT DECODED: ' . ($resultEncoded) . "\n";
 		print 'GALILEO RESULT DECODED LENGTH: ' . strlen($resultEncoded) . "\n";
 		print 'GALILEO RESULT IS DECRYPTED: ' . ($isDecrypted ? 'TRUE' : 'FALSE') . "\n";
 		print 'GALILEO RESULT DECRYPTED: ' . print_r($xmlTextDecrypted, true) . "\n";
		return array();
	}
	
	/**
	 * строим данные для запроса
	 * @param array $query массив запросов (см. Whale_Timetable_Abstract::$_defaultQuery)
	 * @return array $data
	 * @see Whale_Timetable_Abstract::_buildData()
	 */
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
//  		$xmlData->addChild('Sort', 'time'); 
//  		$xmlData->addChild('SortType', 'ASC'); 
		$xmlData->addChild('Origin', $query['city_from']);
		$xmlData->addChild('Destination', $query['city_to']);
// 		$xmlData->addChild('DepDate', $query['date_to']);
//  		$xmlData->addChild('ReturnDate', $query['date_back']);
//  		$xmlData->addChild('Class', $this->_airClassMap[$query['air_class']]);
//  		$xmlData->addChild('AdtNumber', $query['adult']);
//  		$xmlData->addChild('ChdNumber', $query['child']);
//  		$xmlData->addChild('InfNumber', $query['infant']);
//  		$xmlData->addChild('Throw', $query['one_flight'] ? 'true' : 'false');
//  		$xmlData->addChild('Type', 'avia');
		
		$xmlText = $xmlData->asXML();
		
		// получаем ключ для шифрования
		$publicKey = openssl_get_publickey('file:///home/xu/workspace/aerogate/ServerKeyPublic.pem');
		$xmlTextEncrypted = '';
		
		// пытаемся зашифровать
		$isEncrypted = openssl_public_encrypt($xmlText, $xmlTextEncrypted, $publicKey);
		
		if (defined('WHALE_TIMETABLE_DEBUG') && WHALE_TIMETABLE_DEBUG) {
			print_r($xmlText);
			print_r(strlen($xmlText) * 8);
			print  "ENCRYPTED: " . $isEncrypted . "\n";
		}
		
		$xmlTextEncoded = base64_encode($xmlTextEncrypted);
		
		$request = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><request></request>');
		$request->addChild('ClientId', $this->_clientId);
		$request->addChild('XmlText', $xmlTextEncoded);
		
		return array('query' => $request->asXML());
	}
}
