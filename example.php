<?php
// composer autoloader
require 'vendor/autoload.php';

// namespace config
use \Pardot\API as API;

//See Config source for more details
$pardot_config = new \Pardot\Config(
    array(
    'email' => "<YOUR EMAIL>",
    'password' => "<YOUR PASSWORD>",
    'userkey' => "<YOUR USER KEY>",
    )
);

// read email by id
echo 'Get email by ID:' . "\n";
$email = API::Instance($pardot_config)->doOperationById(API::OBJ_EMAIL, API::OPR_READ, 447822116);
var_dump($email['response']->email);

// read prospect by email
echo 'Get prospect by email:' . "\n";
$prospect = API::Instance($pardot_config)->doOperationByField(
    API::OBJ_PROSPECT,
    API::OPR_READ,
    'email',
    'email@example.com'
);
var_dump($prospect);

// read all prospects updated in the last 15 minutes
$prospects = API::Instance($pardot_config)->queryObject(API::OBJ_PROSPECT, array('updated_after' => '15 minutes ago'));
var_dump($prospects);

//read all forms created after 10 days
$forms = API::Instance($pardot_config)->queryObject(API::OBJ_FORM, array('created_after' => '10 days ago'));
var_dump($forms);
