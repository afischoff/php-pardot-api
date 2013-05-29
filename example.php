<?php

// class auto load handler
spl_autoload_register(function ($class) {
	$parts = explode('\\', $class);
	require strtolower( end($parts) ) . '.class.php';
});

// namespace config
use \Pardot\API as API;
/*
// read email by id
echo 'Get email by ID:' . "\n";
$email = API::Instance()->doOperationById(API::OBJ_EMAIL, API::OPR_READ, 447822116);
var_dump($email['response']->email);

echo "\n\n";


// read prospect by email
echo 'Get prospect by email:' . "\n";
$prospect = API::Instance()->doOperationByField(API::OBJ_PROSPECT,
								API::OPR_READ,
								'email',
								'email@example.com');
var_dump($prospect);
*/
// read all prospects updated in the last 15 minutes
$prospects = API::Instance()->queryObject(API::OBJ_PROSPECT, array('updated_after' => '15 minutes ago'));
var_dump($prospects);