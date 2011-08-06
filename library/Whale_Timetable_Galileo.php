<?php
/**
 * Класс для получения рейсов с помощью шлюза Galileo
 * @author Dmitry Groza (boxfrommars@gmail.com)
 */
class Whale_Timetable_Galileo extends Whale_Timetable_Abstract
{
	// id клиента
	protected $_clientId;
	
	// приватный ключ клиента для расшифровки ответов от сервера
	protected $_clientPrivateKey;
	
	// публичный ключ сервера для rsa-шифрования запросов 
	protected $_serverPublicKey;
	
	// соответствия между классами пассажира в системе галилео и нашим форматом
	protected $_airClassMap = array(
		'1' => 'E',
		'2' => 'B',
		'3' => 'F',
	);
	
	/**
	 * устанавливаем id клиента, и ключи для шифрования/расшифровки
	 * @param array $options
	 * @throws Exception если отсутствует один из параметров (client_id, server_public_key и client_private_key)
	 */
	public function __construct($options)
	{
		if (empty($options['client_id'])) {
			throw new Exception('client_id option for Galileo is missing');
		} else {
			$this->_clientId = $options['client_id'];
		}
		if (empty($options['server_public_key'])) {
			throw new Exception('server_public_key option for Galileo is missing');
		} else {
			$this->_serverPublicKey = $options['server_public_key'];
		}
		if (empty($options['client_private_key'])) {
			throw new Exception('client_private_key option for Galileo is missing');
		} else {
			$this->_clientPrivateKey = $options['client_private_key'];
		}
		parent::__construct($options);
	}
	
	/**
	 * на основе ответа стоим список рейсов. сначала декодируем из base64, потом 
	 * @see Whale_Timetable_Abstract::_buildTimetable()
	 */
	protected function _buildTimetable($result)
	{
		// если возвратился ответ с ошибками 1, 2, 3, то ответ не закодирован, поэтому сначала проверяем 
		// не является ли ответ незаифрованным sql, и какая ошибка в нём содержится
		try {
            @$xmlData = new SimpleXMLElement($result);	
            if ($xmlData->ErrorCode > 0) {
                print_r($xmlData);
                return array();
            }; // ошибки 0, 1, 2
		} catch (Exception $e) {
			// не является, значит всё хорошо, будем расшифровывать
		} 
			
 		$resultEncoded = base64_decode($result);
 		$resultEncrypted = $this->_decryptRSA($resultEncoded, $this->_clientPrivateKey);
 //		file_put_contents('/tmp/galileo.xml', $resultEncrypted);

 		$xmlTimetable = new SimpleXMLElement($resultEncrypted);
        
		if (!empty($xmlTimetable->ErrorCode)) {
            print_r($xmlTimetable);
            return array(); // ошибки 3, 4
		}
		
		$timetable = $this->_parseXML($xmlTimetable);
		return $timetable;
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
		
		$xmlData = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><pricing></pricing>');
		$xmlData->addChild('Origin', $query['city_from']);
 		$xmlData->addChild('Destination', $query['city_to']);
		$xmlData->addChild('DepDate', $query['date_to']);
  		$xmlData->addChild('ReturnDate', $query['date_back']);
 		$xmlData->addChild('Class', $this->_airClassMap[$query['air_class']]);
 		$xmlData->addChild('AdtNumber', $query['adult']);
 		$xmlData->addChild('ChdNumber', $query['child']);
 		$xmlData->addChild('InfNumber', $query['infant']);
 		$xmlData->addChild('Through', $query['one_flight'] ? 'true' : 'false');
 		$xmlData->addChild('Type', 'avia');

		$xmlText = $xmlData->asXML();
		
		$xmlTextEncrypted = $this->_encryptRSA($xmlText, $this->_serverPublicKey);
		$xmlTextEncoded = base64_encode($xmlTextEncrypted);
		
		$request = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><request></request>');
		$request->addChild('ClientId', $this->_clientId);
		$request->addChild('XmlText', $xmlTextEncoded);
		
		return array('query' => $request->asXML());
	}
	
	protected function _parseXml($xmlTimetable)
	{
		$timetable = array();
		
		foreach ($xmlTimetable->Variant as $variant) {
			$segments = array();
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
					'class' => array_search((string) $xmlSegment->BaseClass, $this->_airClassMap),
					'flight_time' => (string) $xmlSegment->FlightTime,
					'descr' => '',
					'segment_num' => $segmentCounter++, 
				);
				$segments[] = $segment;
			}
			
			$summary = array(
				'gateway' => 'galileo',
				'price' => (string) $variant->TotalPrice,
//				'offerdate' => time(),
//				'live' => NULL,
//				'status' => 1,
//				'user_id' => 1,
//				'offer_id' => 1,
				'segments' => $segments,
			);
			
			$timetable[] = $summary;
		}
		return $timetable;
	}
	
	// по-хорошему, то что ниже не относится к этому классу, а является просто функциями, но чтобы не засорять пространство
	// пусть побудут методами
	
	/**
	 * расшифровываем с помощью rsa-ключа т.к. данные могут быть длиннее ключа, то сначала режем 
	 * по 128 символов (для 1024 ключа) и расшифровываем кусочки, которые склеиваем снова.
	 * @param string $encryptedString зашифрованная 1024-битным ключом строка
	 * @param string $privateKey путь к приватному ключу для расшифровки (в формате "file:///home/user/.../private.pem")
	 */
	protected function _decryptRSA($encryptedString, $privateKey) 
	{
		$decryptedString = '';
		$encryptedParts = str_split($encryptedString, 128);
		
		$privateKey = openssl_get_privatekey($privateKey);
		foreach ($encryptedParts as $part) {
			openssl_private_decrypt($part, $decryptedPart, $privateKey);
			$decryptedString .= $decryptedPart;
		}
		return $decryptedString;
	}
	
	/**
	* шифруем с помощью rsa-ключа т.к. данные могут быть длиннее ключа, то сначала режем
	* по 128 символов (для 1024 ключа) и шифруем части, которые склеиваем снова.
	* @param string $string строка для шифрования 1024-битным ключом
	* @param string $publicKey путь к публичному ключу для шифрования (в формате "file:///home/user/.../public.pem")
	*/
	protected function _encryptRSA($string, $publicKey)
	{
		$encryptedString = '';
		$stringParts = str_split($string, 100);
		
		$publicKey = openssl_get_publickey($publicKey);
		foreach ($stringParts as $part) {
			openssl_public_encrypt($part, $encryptedPart, $publicKey);
			$encryptedString .= $encryptedPart;
		}
		return $encryptedString;
	}
}
