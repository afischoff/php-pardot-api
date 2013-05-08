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

	// API Error Codes
	const ERROR_INVALID_KEY = 1; // invalid or expired

	private $email;
	private $password;
	private $connection;
	private $debug;
	private $logfile;

	public $postFields;

	/**
	 * Singleton instance generator
	 *
	 * @return API
	 */
	public static function Instance()
	{
		static $inst = null;
		if ($inst === null) {
			$inst = new static;
		}
		return $inst;
	}

	/**
	 * Constructor method for new API wrapper instances
	 */
	protected function __construct() {
		// load config from file
		include(__DIR__ . DIRECTORY_SEPARATOR . 'pardot_config.php');

		// exit here if we can't load a configuration file
		if (empty($pardot_config)) {
			die('FATAL No pardot_config.php file found');
		}

		// store initialization values
		$this->email = $pardot_config['email'];
		$this->password = $pardot_config['password'];
		$this->connection = $pardot_config['connection'];
		$this->debug = $pardot_config['debug'];
		$this->logfile = $pardot_config['logfile'];

		// set default post fields
		$this->postFields = array(
			'format' => 'json',
			'user_key' => $pardot_config['user_key']
		);

		// authenticate or exit here
		if ( ! $this->authenticate() ) {
			die('FATAL Pardot API Authentication Failed!');
		}
	}

	/**
	 * prevent clone function from cloning this class
	 */
	private function __clone() {}

	/**
	 * prevent unserialize() from instantiating this class
	 */
	private function __wakeup() {}

	/**
	 * Function performs an operation on a single Pardot object as defined by $id or $email
	 *
	 * @param string $object - The objects exposed through the API (list, opportunity, prospect, prospectAccount, user, visit, visitor)
	 * @param string $operation - The operation to perform (read, update, delete, undelete)
	 * @param int $id - The ID of the object being affected
	 * @param string $email - The email address of the object being affected
	 * @param array $parameters - An array of field => value pairs to be set.
	 * @return array
	 */
	public function doOperationByIdOrEmail($object, $operation, $id = null, $email = null, $parameters = null) {
		// setup default return structure
		$returnStructure = array(
			'success' => false,
			'err_msg' => null
		);

		// validate inputs - $id or $email is required
		if (is_null($id) && is_null($email)) {
			// debug message
			$errMsg = 'FATAL: doOperationByIdOrEmail() Invalid input - $id or $email is required';
			$this->debugLog($errMsg);

			// update return structure
			$returnStructure['err_msg'] = $errMsg;

			// return
			return $returnStructure;
		}

		// build request URL
		$url = self::URI . $object . self::VERSION . '/do/' . $operation;

		if ( ! is_null($id)) {
			$url .= '/id/' . $id;
		} else {
			$url .= '/email/' . $email;
		}

		// merge post fields and parameters
		if (is_array($parameters)) {
			$this->postFields = array_merge($this->postFields, $parameters);
		}

		// do request and return
		return $this->makeRequest($url, $this->postFields);
	}

	/**
	 * Performs a query on a Pardot object
	 *
	 * @param string $object - The objects exposed through the API (list, opportunity, prospect, prospectAccount, user, visit, visitor)
	 * @param array $parameters - An array of field => value pairs to be set. Also used for limit and offset
	 * @return array
	 */
	public function queryObject($object, $parameters = null) {
		// build request URL
		$url = self::URI . $object . self::VERSION . '/do/query';

		// merge post fields and parameters
		if (is_array($parameters)) {
			$this->postFields = array_merge($this->postFields, $parameters);
		}

		// do request and return
		return $this->makeRequest($url, $this->postFields);
	}

	/**
	 * Makes all API requests automatically authenticating if the api_key expires.
	 *
	 * @param $url
	 * @param $postFields
	 * @return array
	 */
	public function makeRequest($url, $postFields = null) {
		// setup default return structure
		$returnStructure = array(
			'success' => false,
			'response' => null
		);

		// try api request
		$resp = $this->sendPostRequest($url, $postFields);

		// return if response is invalid
		if ( ! $resp['success']) {
			return $returnStructure;

		} else {
			// if response is successful, return. Else if api_key expired, authenticate and try again
			if ($resp['resp_decoded']->{'@attributes'}->stat == self::STAT_SUCCESS) {
				// update return structure
				$returnStructure['success'] = true;
				$returnStructure['response'] = $resp['resp_decoded'];

				// return
				return $returnStructure;

			} else if ($resp['resp_decoded']->{'@attributes'}->err_code == self::ERROR_INVALID_KEY) {

				// API key expired - try authenticating again
				if ( ! $this->authenticate() ) {
					// return
					return $returnStructure;
				}

				// try api request again
				$resp = $this->sendPostRequest($url, $postFields);

				// if response is successful, return
				if ($resp['resp_decoded']->{'@attributes'}->stat == self::STAT_SUCCESS) {
					// update return structure
					$returnStructure['success'] = true;
					$returnStructure['response'] = $resp['resp_decoded'];

					// return
					return $returnStructure;

				} else {
					// return
					return $returnStructure;
				}

			}

			// should never get here
			return $returnStructure;
		}
	}

	/**
	 * Makes an authentication request and stores the api_key for future requests
	 *
	 * @return bool
	 */
	private function authenticate() {
		// build request URL
		$url = self::URI . 'login' . self::VERSION;

		// compile post fields
		$postFields = $this->postFields;
		$postFields['email'] = $this->email;
		$postFields['password'] = $this->password;

		// debug messages
		$this->debugLog('Trying to authenticate...');

		// send request
		$resp = $this->sendPostRequest($url, $postFields);

		// if successful, store the api key and return
		if ($resp['success']
			&& $resp['resp_decoded']->{'@attributes'}->stat == self::STAT_SUCCESS
			&& $resp['resp_decoded']->api_key) {
				// debug messages
				$this->debugLog('Authentication Successful!');

				// store api key
				$this->postFields['api_key'] = $resp['resp_decoded']->api_key;

				return true;

		} else {
			// debug messages
			$this->debugLog('Authentication Failed!');

			// unset api key
			unset($this->postFields['api_key']);

			return false;
		}
	}

	/**
	 * Sends HTTP post requests to the API and handles the response
	 *
	 * @param string $url - The complete URL to be requested
	 * @param array $postFields - Post fields in array form
	 * @return array return structure
	 */
	private function sendPostRequest($url, $postFields = null) {
		// setup default return structure
		$returnStructure = array(
			'success' => false,
			'resp_code' => null,
			'resp_body' => null,
			'resp_decoded' => null
		);

		// use default post fields if none are passed in
		if (is_null($postFields)) {
			$postFields = $this->postFields;
		}

		// build query string from $postFields
		$postFieldsString = '';
		foreach ($postFields as $field => $value) {
			// handle boolean values
			if ($value === true) {
				$value = 'true';
			} else if ($value === false) {
				$value = 'false';
			}

			// append to string
			$postFieldsString .= $field . '=' . urlencode($value) . '&';
		}
		$postFieldsString = substr($postFieldsString, 0, -1);

		// debug messages
		$this->debugLog('Making post request: ' . $url);
		$this->debugLog('Posting variables: ' . $postFieldsString);

		// handle http post by connection type
		switch ($this->connection) {
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

				// if an error happened, log and return
				if ($returnStructure['resp_body'] === false) {
					// log error message
					$this->debugLog('FATAL cURL error: ' . curl_error($ch));

					// close connection
					curl_close($ch);

					// return
					return $returnStructure;
				}

				// close connection
				curl_close($ch);

				break;
		}

		// debug messages
		$this->debugLog('Response code received: ' . $returnStructure['resp_code']);
		$this->debugLog('Response body received: ' . $returnStructure['resp_body']);

		// convert response to data object
		switch ($postFields['format']) {
			case 'json':
				// decode JSON string
				$dataObj = json_decode($returnStructure['resp_body']);

				// update return structure
				if ( ! is_null($dataObj)) {
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
	private function debugLog($message) {
		// echo debug message if debug mode is enabled
		if ($this->debug) {
			echo $message . "\n";
		}

		$timeStr = new \DateTime();
		$timeStr = $timeStr->format(\DateTime::ISO8601);

		// append debug messages to log file
		if ( ! is_null($this->logfile)) {
			error_log($timeStr . ' {Pardot API} ' . $message . "\n", 3, $this->logfile);
		} else {
			error_log($timeStr . ' {Pardot API} ' . $message . "\n");
		}
	}

}
