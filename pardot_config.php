<?php
$pardot_config = array(

	/*
	 * Pardot User settings:
	 */
	'email' => '',
	'password' => '',
	'user_key' => '', // found here: https://pi.pardot.com/account

	/*
	 * HTTP connection handler
	 */
	'connection' => '', // defaults to cURL if left blank

	/*
	 * Debugging and logging settings
	 */
	'debug' => false, // echos debug info to the screen
	'logging' => true, // turns file based debug info logging on/off
	'logfile' => 'pardot.log' // logs debug info to file. If this is empty, debug info will be logged in the PHP error log
);
