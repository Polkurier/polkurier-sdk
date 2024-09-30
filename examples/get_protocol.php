<?php
require('../autoload.php');
require('./config.php');

use PolkurierWebServiceApi\Auth;
use PolkurierWebServiceApi\Config;
use PolkurierWebServiceApi\Exception\ErrorException;
use PolkurierWebServiceApi\Methods\GetProtocol;
use PolkurierWebServiceApi\PolkurierWebService;

$config = new Config();
$config->setAuthLogin(API_LOGIN);
$config->setAuthToken(API_TOKEN);

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


