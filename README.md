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
	1. <doOperationById($object, $operation, $id = null, $parameters = null)>
	2. <doOperationByField($object, $operation, $field = null, $fieldValue = null, $parameters = null)>
	3. <queryObject($object, $parameters = null)>

The <$object> and <$operation> values are the constants referenced at the top of the class file.

See the example.php file for complete usage examples.
