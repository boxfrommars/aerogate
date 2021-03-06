Классы для работы с api шлюзов Галилео и Сирена
===============================================

* Класс для работы с api Сирены (Whale_Timetable_Siren)
* Класс для работы с api Галилео (Whale_Timetable_Galileo)
* Класс-пул для шлюзов (Whale_Timetable_Pull)
* Модель для сохранения результатов в БД (Whale_Timetable_Model)

использование примера index.php: 
--------------------------------
положить сюда ключи, создать config.php (по подобию config.ex.php)

использование Whale_Timetable_Pull:
-----------------------------------

	$TimetablePull = new Whale_Timetable_Pull(array(
		'gateways' => array(
			'siren' => array(
				'password' => 'password',
				'url' => 'http://gateway.com/siren/'
			),
			'galileo' => array(
				'client_id' => '1',
				'url' => 'http://gateway.com/galileo/'
				'server_public_key' => 'file://' . __DIR__ . '/ServerKeyPublic.pem',    // путь к публичному ключу сервера
				'client_private_key' => 'file://' . __DIR__ . '/ClientKeyPrivate.pem',  // путь к приватному ключу клиента
			),
		),
	));
	
	$query = array(
		'city_from' => 'MOW',
		'city_to' => 'LED',
		'date_to' => '11.11.2011',
		'date_back' => '', // необязательный
		'air_class' => 1, // необязательный
		'one_flight' => 0, // необязательный
		'adult' => 1, // необязательный
		'child' => 0, // необязательный
		'infant' => 0 // необязательный
	);
	$timetable = Whale_Timetable_Pull->getTimetable($query);

теперь $timetable содержит массив вида:

	Array
	(
		[0] => Array
		(
			[gateway] => siren
			[price] => 3475.00
			[segments] => Array
			(
				[0] => Array
				(
					[price] => 2350.00
					[datefly] => 03.10.11
					[timedep] => 07:00
					[timearr] => 09:15
					[airportdep] => ПЛК
					[airportarr] => ДМД
					[airfly] => U6
					[airplane] => 320
					[class] => Y
					[flight_time] => 2:15
					[descr] => 
					[segment_num] => 0
				)
				
					[1] => Array
					(
					[price] => 1125.00
					[datefly] => 05.10.11
					[timedep] => 11:05
					[timearr] => 12:25
					[airportdep] => ВНК
					[airportarr] => ПЛК
					[airfly] => ЮТ
					[airplane] => ТУ5
					[class] => Ц
					[flight_time] => 1:20
					[descr] => 
					[segment_num] => 1
				)
			
			)
		)
		[1] => array()
	
		...
	)

то есть массив предложений. предложение побито на сегменты (перелёты). обратный рейс тоже является сегментом (сегментами)

использование Whale_Timetable_Model:
------------------------------------

	$pdo = new PDO('mysql:host=localhost;dbname=aero', 'username', 'password', 
		array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
	$TimetableModel = new Whale_Timetable_Model($pdo);
	$TimetableModel->save($timetable, $userId, $orderId, $live);

также можно получить код, для запроса к шлюзу по id города/страны/аэропорта/самолёта/аэрокомпании из базы:

	echo $TimetableModel->getAircompanyCode(3) . "\n";
	echo $TimetableModel->getAirportCode(3) . "\n";
	echo $TimetableModel->getAirplaneCode(3) . "\n";
	echo $TimetableModel->getCityCode(3) . "\n";
	echo $TimetableModel->getCountryCode(3) . "\n";

и наоборот (правда пока это используется только внутри Whale_Timetable_Model для сохранения таблицы предложений) можно
по коду (неважно, ru или en) получить id аэропорта/самолёта/компании из базы:


	$TimetableModel = new Whale_Timetable_Model($pdo);
	echo $TimetableModel->getAircompanyId('VV') . "\n";
	echo $TimetableModel->getAirportId('SVO') . "\n";
	echo $TimetableModel->getAirplaneId('737') . "\n";

пример базы для Whale_Timetable_Model в папке deploy 
