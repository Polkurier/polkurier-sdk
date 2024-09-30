<?php
require('../autoload.php');
require('./config.php');

use PolkurierWebServiceApi\Auth;
use PolkurierWebServiceApi\Config;
use PolkurierWebServiceApi\Methods\DeleteAddress;
use PolkurierWebServiceApi\PolkurierWebService;

try {
    $config = new Config();
    $config->setAuthLogin(API_LOGIN);
    $config->setAuthToken(API_TOKEN);

    $auth = new Auth($config);
    $webApi = new PolkurierWebService($auth, $config);

    $method = new DeleteAddress();
    $method->setType('SENDER');
    $method->setId('VcE');

    $webApi->requestMethod($method);
    var_dump($method->getData());

} catch (Exception $ex) {
    echo $ex->getMessage();
}
