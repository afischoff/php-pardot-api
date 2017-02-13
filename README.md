php-pardot-api
==============

PHP wrapper for the Pardot RESTful API

# Requirements #
1. PHP 5.3.*
2. cURL library installed
3. Install directory should be writable (not required, but strongly encouraged)

# Configuration #
1. Set the email, password, and user_key values in the pardot_config.php file
2. Set the debug mode and logging settings in the pardot_config.php file (optional)

# Usage #
1. Set the namespace: <code>use \Pardot\API as API;</code>
2. Make requests using the 3 main request functions:
	1. <code>doOperationById($object, $operation, $id = null, $parameters = null)</code>
	2. <code>doOperationByField($object, $operation, $field = null, $fieldValue = null, $parameters = null)</code>
	3. <code>queryObject($object, $parameters = null)</code>

The <code>$object</code> and <code>$operation</code> values are the constants referenced at the top of the class file.

<pre>
<code>
&lt;?php
// namespace config
use \Pardot\API as API;
$pardot_config = new \Pardot\Config(
    array(
    'email' => "<YOUR PARDOT EMAIL>",
    'password' => "<YOUR PARDOT PASSWORD>",
    'userkey' => "<YOUR PARDOT USERKEY>",
    )
);
// get all prospects updated within the last 2 hours
$prospects = API::Instance($pardot_config)->queryObject(API::OBJ_PROSPECT, array('updated_after' => '2 hours ago'));
var_dump($prospects);
</code>
</pre>

See the example.php file for complete usage examples.
