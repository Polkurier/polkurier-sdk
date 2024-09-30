<?php
require('../autoload.php');
require('./config.php');

use PolkurierWebServiceApi\Auth;
use PolkurierWebServiceApi\Config;
use PolkurierWebServiceApi\Exception\ErrorException;
use PolkurierWebServiceApi\Methods\GetLabel;
use PolkurierWebServiceApi\PolkurierWebService;

$config = new Config();
$config->setAuthLogin(API_LOGIN);
$config->setAuthToken(API_TOKEN);

$auth = new Auth($config);
$webApi = new PolkurierWebService($auth, $config);

try {
    $method = new GetLabel();
    $method->addOrderNumber('30692-136');
    $webApi->requestMethod($method);

    header('Content-Type: application/pdf');
    echo $method->getData()->getContent();

} catch (ErrorException $ex) {
    echo $ex->getMessage();
}
