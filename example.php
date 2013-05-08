<?php

// class auto load handler
spl_autoload_register(function ($class) {
	$parts = explode('\\', $class);
	require strtolower( end($parts) ) . '.class.php';
});

// read email by id
$email_api = \Pardot\Email::Instance()->getById(447822116);
var_dump($email_api);