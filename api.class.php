<?php namespace Pardot;

/**
 * Class API
 *
 * Pardot singleton REST/JSON API wrapper
 *
 * @package Pardot
 * @author Andy Fischoff <andy.fischoff@pardot.com>
 */

class API
{
	// API wrapper constants
	const API_KEY_FILE = 'pardot_api_key';
	const URI = 'https://pi.pardot.com/api/';
	const VERSION = 3;
	const STAT_SUCCESS = 'ok';

	// API Error Code constants
	const ERR_INVALID_KEY = 1; // invalid or expired

	// API Object constants
	const OBJ_EMAIL = 'email';
	const OBJ_LIST = 'list';
	const OBJ_OPPORTUNITY = 'opportunity';
	const OBJ_PROSPECT = 'prospect';
	const OBJ_PROSPECT_ACCOUNT = 'prospectAccount';
	const OBJ_USER = 'user';
	const OBJ_VISIT = 'visit';
	const OBJ_VISITOR = 'visitor';

	// API Operation constants
	const OPR_CREATE = 'create';
	const OPR_READ = 'read';
	const OPR_UPDATE = 'update';
	const OPR_DELETE = 'delete';
	const OPR_UNDELETE = 'undelete';
	const OPR_QUERY = 'query';

	// private instance variables
	private $email;
	private $password;
	private $connection;
	private $debug;
	private $logging;
	private $logfile;

	// public instance variables
	public $postFields;

	/**
	 * Singleton instance generator
	 *
	 * @return static API class
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

		// set defaults
		if (empty($pardot_config['connection']))	$pardot_config['connection'] = 'cURL';
		if (empty($pardot_config['debug'])) 		$pardot_config['debug'] = false;
		if (empty($pardot_config['logging'])) 		$pardot_config['logging'] = false;
		if (empty($pardot_config['logfile'])) 		$pardot_config['logfile'] = 'pardot.log';

		// store initialization values
		$this->email = $pardot_config['email'];
		$this->password = $pardot_config['password'];
		$this->connection = $pardot_config['connection'];
		$this->debug = $pardot_config['debug'];
		$this->logging = $pardot_config['logging'];
		$this->logfile = $pardot_config['logfile'];

		// set default post fields
		$this->postFields = array(
			'format' => 'json',
			'user_key' => $pardot_config['user_key']
		);

		// try to read api_key from file
		$api_key_file = __DIR__ . DIRECTORY_SEPARATOR . self::API_KEY_FILE;
		if (file_exists($api_key_file)) {
			$this->postFields['api_key'] = file_get_contents($api_key_file);
		}

		// authenticate or exit here
		if (empty($this->postFields['api_key']) && ! $this->authenticate() ) {
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
	 * Function performs an operation on a single Pardot object as defined by $id
	 * This makes a request with the URL: https://pi.pardot.com/api/<$object>/version/3/do/<$operation>/id/<$id>
	 *
	 * @param string $object - The objects exposed through the API (see OBJ_* constants above)
	 * @param string $operation - The operation to perform (see OPR_* constants above)
	 * @param int $id - The ID of the object being affected
	 * @param array $parameters - An array of field => value pairs to be set.
	 * @return array
	 */
	public function doOperationById($object, $operation, $id = null, $parameters = null) {
		// setup default return structure
		$returnStructure = array(
			'success' => false,
			'err_msg' => null
		);

		// validate input - $id is required
		if (is_null($id)) {
			// debug message
			$errMsg = 'FATAL: doOperationById() Invalid input - $id is required';
			$this->debugLog($errMsg);

			// update return structure
			$returnStructure['err_msg'] = $errMsg;

			// return
			return $returnStructure;
		}

		// build request URL
		$urlparams = array('do' => $operation, 'id' => $id);
		$url = $this->buildURL($object, $urlparams);

		// merge post fields and parameters
		if (is_array($parameters)) {
			$this->postFields = array_merge($this->postFields, $parameters);
		}

		// do request and return
		return $this->makeRequest($url, $this->postFields);
	}

	/**
	 * Function performs an operation on a single Pardot object as defined by $field and $fieldValue
	 * This makes a request with the URL:
	 * https://pi.pardot.com/api/<$object>/version/3/do/<$operation>/<$field>/<$fieldValue>
	 *
	 * @param string $object - The objects exposed through the API (see OBJ_* constants above)
	 * @param string $operation - The operation to perform (see OPR_* constants above)
	 * @param string $field - The reference field of the object being affected
	 * @param string $fieldValue - The value of field referenced in $field
	 * @param array $parameters - An array of field => value pairs to be set.
	 * @return array
	 */
	public function doOperationByField($object, $operation, $field = null, $fieldValue = null, $parameters = null) {
		// setup default return structure
		$returnStructure = array(
			'success' => false,
			'err_msg' => null
		);

		// validate inputs - $id or $email is required
		if (is_null($field) || is_null($fieldValue)) {
			// debug message
			$errMsg = 'FATAL: doOperationByField() Invalid input - $field and $fieldValue are required';
			$this->debugLog($errMsg);

			// update return structure
			$returnStructure['err_msg'] = $errMsg;

			// return
			return $returnStructure;
		}

		// build request URL
		$urlparams = array('do' => $operation, $field => $fieldValue);
		$url = $this->buildURL($object, $urlparams);

		// merge post fields and parameters
		if (is_array($parameters)) {
			$this->postFields = array_merge($this->postFields, $parameters);
		}

		// do request and return
		return $this->makeRequest($url, $this->postFields);
	}

	/**
	 * Performs a query on a Pardot object
	 * This makes a request with the URL: https://pi.pardot.com/api/<$object>/version/3/do/query
	 *
	 * @param string $object - The objects exposed through the API (see OBJ_* constants above)
	 * @param array $parameters - An array of field => value pairs to be set. Also used for limit and offset
	 * @return array
	 */
	public function queryObject($object, $parameters = null) {
		// build request URL
		$urlparams = array('do' => 'query');
		$url = $this->buildURL($object, $urlparams);

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

			} else if ($resp['resp_decoded']->{'@attributes'}->err_code == self::ERR_INVALID_KEY) {

				// API key expired - try authenticating again
				if ( ! $this->authenticate() ) {
					// return
					return $returnStructure;
				}

				// merge in new API key
				$postFields = array_merge($postFields, $this->postFields);

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
	 * @return bool whether authentication was successful
	 */
	private function authenticate() {
		// build request URL
		$url = $this->buildURL('login');

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

				// store in file for future requests
				$file_written = file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . self::API_KEY_FILE,
					$this->postFields['api_key']);

				if ( ! $file_written) {
					$this->debugLog("Warning: can't write api key to file: " . self::API_KEY_FILE);
				}

				return true;

		} else {
			// debug messages
			$this->debugLog('Authentication Failed!');

			// unset api key
			unset($this->postFields['api_key']);

			// delete stored api file
			if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . self::API_KEY_FILE)) {
				unlink(__DIR__ . DIRECTORY_SEPARATOR . self::API_KEY_FILE);
			}

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

		// convert boolean to string
		foreach ($postFields as &$value) {
			if ($value === true) {
				$value = 'true';
			} else if ($value === false) {
				$value = 'false';
			}
		}

		// build post field string
		$postFieldsString = http_build_query($postFields);

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
	 * @param string $message the message which gets written to the logs
	 */
	private function debugLog($message) {
		// echo debug message if debug mode is enabled
		if ($this->debug) {
			echo $message . PHP_EOL;
		}

		// append debug messages to log file
		if ($this->logging) {

			// get timestamp
			$timeStr = new \DateTime();
			$timeStr = $timeStr->format(\DateTime::ISO8601);

			// log to separate file or PHP error log
			if ( ! is_null($this->logfile)) {
				error_log($timeStr . ' {Pardot API} ' . $message . PHP_EOL, 3, $this->logfile);
			} else {
				error_log($timeStr . ' {Pardot API} ' . $message . PHP_EOL);
			}
		}
	}

	/**
	 * Used for building API request URLs
	 *
	 * @param string $object - API object (see OBJ_* constants)
	 * @param array $params - array of key/val pairs for building the URL
	 * @return string - formatted request string
	 */
	private function buildURL($object, $params = null) {
		$url  = self::URI . $object . '/version/' . self::VERSION . '/';
		if ($params) {
			foreach ($params as $field => $value) {
				$url .= urlencode($field) . '/' . urlencode($value) . '/';
			}
		}
		return $url;
	}

}
