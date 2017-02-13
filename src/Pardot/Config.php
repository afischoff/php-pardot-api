<?php
namespace Pardot;

class Config
{
    // private instance variables
    private $email;
    private $password;
    private $userkey; // found here: https://pi.pardot.com/account
    private $connection = 'cURL';
    private $debug = false;// echos debug info to the screen
    private $logging = false; // turns file based debug info logging on/off
    private $logfile = 'pardot.log';// logs debug info to file. If this is empty, debug info will be logged in the PHP error log
    private $apikeyfile = "/tmp/pardot_api_key";

    function __construct($config_array)
    {
        foreach($config_array as $key => $value) {
            $this->$key = $value;
        }
    }

    function __call($method, $params)
    {
        $var = strtolower(substr($method, 3));
        if (strncasecmp($method, "get", 3) > -1) {
            return $this->$var;
        }
        if (strncasecmp($method, "set", 3) > 1) {
            $this->$var = $params[0];
        }
    }
}
?>
