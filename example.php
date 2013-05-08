<?php

// class auto load handler
spl_autoload_register(function ($class) {
	$parts = explode('\\', $class);
	require strtolower( end($parts) ) . '.class.php';
});

// namespace config
use \Pardot\API as API;

// read email by id
$email = API::Instance()->doOperationById(API::OBJ_EMAIL, API::OPR_READ, 447822116);
var_dump($email['response']->email);