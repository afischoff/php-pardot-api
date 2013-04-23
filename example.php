<?php

// class auto load handler
spl_autoload_register(function ($class) {
	$parts = explode('\\', $class);
	require strtolower( end($parts) ) . '.class.php';
});

// instantiate new Pardot client
$pardot_client = new \Pardot\API('andy.fischoff+api@pardot.com', 'Pardot07!23', '3c177b33f38d3150ebddb4f9d6ff36c8');

// if not authenticated, stop here.
if ( empty($pardot_client->postFields['api_key']) )
{
	die('Pardot Authentication Failed!');
}

// read prospect by id
$prospect = $pardot_client->doOperationByIdOrEmail('prospect', 'read', 63045632);
echo 'Prospect:';
var_dump($prospect);

// query prospects
$prospectSearch = $pardot_client->queryObject('prospect', array(
	'assigned' => true,
	'score_greater_than' => 100,
	'limit' => 4
));
echo 'Prospect Search:';
var_dump($prospectSearch);
