<?php
require('../autoload.php');

use PolkurierWebServiceApi\Config;
use PolkurierWebServiceApi\PolkurierWebService;
use PolkurierWebServiceApi\Auth;
use PolkurierWebServiceApi\Exception\ErrorException;
use PolkurierWebServiceApi\Methods\GetStatus;

try {

    $config = new Config();
    $auth = new Auth($config);
    $webApi = new PolkurierWebService($auth, $config);
    $method = new GetStatus();
    $method->setOrderNumber('1234-1');
    $webApi->requestMethod($method);
    var_dump($method->getData());

} catch (ErrorException $ex) {
    echo $ex->getMessage();
}
