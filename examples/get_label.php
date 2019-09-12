<?php
require('../autoload.php');

use PolkurierWebServiceApi\Config;
use PolkurierWebServiceApi\Auth;
use PolkurierWebServiceApi\PolkurierWebService;
use PolkurierWebServiceApi\Methods\GetLabel;
use PolkurierWebServiceApi\Exception\ErrorException;

$config = new Config();
$auth = new Auth($config);
$webApi = new PolkurierWebService($auth, $config);

try{
    $method = new GetLabel();
    $method->addOrderNumber('1234-1');
    $webApi->requestMethod($method);

    header('Content-Type: application/pdf');
    echo $method->getData()->getContent();

}catch (ErrorException $ex){
    echo $ex->getMessage();
}

