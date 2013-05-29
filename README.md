php-pardot-api
==============

PHP wrapper for the Pardot RESTful API

# Requirements #
1. PHP 5.3.*
2. cURL library installed
3. Directory must be writable (not required, but strongly encouraged)

# Configuration #
1. Set the email, password, and user_key values in the pardot_config.php file
2. Set the debug mode and logging settings in the pardot_config.php file

# Usage #
1. Set the namespace: <use \Pardot\API as API;>
2. Make requests using the 3 main request functions:
	1. <code>doOperationById($object, $operation, $id = null, $parameters = null)</code>
	2. <code>doOperationByField($object, $operation, $field = null, $fieldValue = null, $parameters = null)</code>
	3. <code>queryObject($object, $parameters = null)</code>

The <code>$object</code> and <code>$operation</code> values are the constants referenced at the top of the class file.

See the example.php file for complete usage examples.
