<?php

// class auto load handler
spl_autoload_register(function ($class) {
	$parts = explode('\\', $class);
	require strtolower( end($parts) ) . '.class.php';
});

// instantiate new Pardot client
$pardot_client = new \Pardot\API('andy.fischoff+api@pardot.com', 'Pardot07!23', '3c177b33f38d3150ebddb4f9d6ff36c8', 'cURL', true);

