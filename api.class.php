<?php namespace Pardot;

/*
| Pardot PHP API Wrapper v1.0
|
|
|
|
|
*/

class API
{
	const URI = 'https://pi.pardot.com/api/';
	const VERSION = '/version/3';
	const STAT_SUCCESS = 'ok';

	private $email;
	private $password;
	private $user_key;
	private $connection;
	private $debug;
	private $logfile;
	private $format;
	private $postFields;
	private $api_key;

	/**
	 * Constructor method for new API wrapper instances
	 *
	 * @param string $email - The API user's email address.
	 * @param string $password - The API user's password.
	 * @param string $user_key - The API user's user_key, as found in the User Menu > Settings
	 * @param string $connection - HTTP request handler. cURL is default
	 * @param bool $debug - When enabled, log messages will be echoed to the screen.
	 * @param null $logfile - When not null, debug messages will be appended to the file
	 */
	public function __construct($email, $password, $user_key, $connection = 'cURL', $debug = false, $logfile = null)
	{
		// store initialization values
		$this->email = $email;
		$this->password = $password;
		$this->user_key = $user_key;
		$this->connection = $connection;
		$this->debug = $debug;
		$this->logfile = $logfile;
		$this->format = 'json';

		// set default post fields
		$this->postFields = array('format' => $this->format);

		// authenticate to get and store the api key
		$this->authenticate();
	}

	/**
	 *
	 */
	public function makeRequest()
	{

	}

	/**
	 * Makes an authentication request and stores the api_key for future requests
	 *
	 * @return bool
	 */
	private function authenticate()
	{
		// form URL
		$url = self::URI . 'login' . self::VERSION;

		// compile post fields
		$postFields = $this->postFields;
		$postFields['email'] = $this->email;
		$postFields['password'] = $this->password;
		$postFields['user_key'] = $this->user_key;

		// debug messages
		$this->debuglog('Trying to authenticate...');

		// send request
		$resp = $this->sendPostRequest($url, $postFields);

		// if successful, store the api key and return
		if ($resp['resp_decoded']->{'@attributes'}->stat == self::STAT_SUCCESS && $resp['resp_decoded']->api_key)
		{
			// debug messages
			$this->debuglog('Authentication Successful! Api_key received: ' . $resp['resp_decoded']->api_key);

			// store api key
			$this->api_key = $resp['resp_decoded']->api_key;

			return true;

		} else {

			// debug messages
			$this->debuglog('Authentication Failed!');

			// unset api key
			$this->api_key = null;

			return false;
		}
	}

	/**
	 * Sends HTTP post requests to the API and handles the response
	 *
	 * @param string $url - The complete URL to be requested
	 * @param array $postFields - Post fields in array form
	 * @return mixed -
	 */
	private function sendPostRequest($url, $postFields)
	{
		// setup default return structure
		$returnStructure = array(
			'success' => false,
			'resp_code' => null,
			'resp_body' => null,
			'resp_decoded' => null
		);

		// build query string from $postFields
		$postFieldsString = '';
		foreach ($postFields as $field => $value)
		{
			$postFieldsString .= $field . '=' . urlencode($value) . '&';
		}
		$postFieldsString = substr($postFieldsString, 0, -1);

		// debug messages
		$this->debuglog('Making post request: ' . $url);
		$this->debuglog('Posting variables: ' . $postFieldsString);

		// handle http post by connection type
		switch ($this->connection)
		{
			case 'cURL':
				// open connection
				$ch = curl_init();

				// set the url, number of POST vars, POST data, and return response
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_POST, count($postFields));
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postFieldsString);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

				// execute request
				$returnStructure['resp_body'] = curl_exec($ch);
				$returnStructure['resp_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

				// close connection
				curl_close($ch);

				break;
		}

		// debug messages
		$this->debuglog('Response code received: ' . $returnStructure['resp_code']);
		$this->debuglog('Response body received: ' . $returnStructure['resp_body']);

		// convert response to data object
		switch ($this->format)
		{
			case 'json':
				// decode JSON string
				$dataObj = json_decode($returnStructure['resp_body']);

				// update return structure
				if ( ! is_null($dataObj))
				{
					$returnStructure['success'] = true;
					$returnStructure['resp_decoded'] = $dataObj;
				}

				break;
		}

		return $returnStructure;
	}

	/**
	 * Optionally writes debug messages to the screen and log file
	 *
	 * @param string $message
	 */
	private function debuglog($message)
	{
		// echo debug message if debug mode is enabled
		if ($this->debug)
		{
			echo $message . "\n";
		}

		// append debug messages to log file
		if ( ! is_null($this->logfile))
		{
			//TODO: get file handler and append info
		}
	}

}
