<?php
require('../autoload.php');

use PolkurierWebServiceApi\Config;
use PolkurierWebServiceApi\Auth;
use PolkurierWebServiceApi\PolkurierWebService;
use PolkurierWebServiceApi\Methods\GetProtocol;
use PolkurierWebServiceApi\Exception\ErrorException;

$config = new Config();
$auth = new Auth($config);
$webApi = new PolkurierWebService($auth, $config);
$method = new GetProtocol();
$method->addOrderNumber('1234-1');

try {
    $webApi->requestMethod($method);

    header('Content-Type: application/pdf');
    echo $method->getData()->getContent();
} catch (ErrorException $ex) {
    echo $ex->getMessage();
}


