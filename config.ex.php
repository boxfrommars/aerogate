<?php
$config = array(
	'gateways' => array(
		'siren' => array(
			'password' => 'password',
			'url' => 'https://example.com/',
		),
 		'galileo' => array(
 			'client_id' => '1',
 			'url' => 'https://example.com/',
			'server_public_key' => 'file://' . __DIR__ . '/ServerKeyPublic.pem',
			'client_private_key' => 'file://' . __DIR__ . '/ClientKeyPrivate.pem',
		),
	)
);

$configDb = array(
	'db' => 'mysql:host=localhost;dbname=aero',
	'user' => 'root',
	'password' => 'password',
);