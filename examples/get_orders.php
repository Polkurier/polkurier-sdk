<?php
require('../autoload.php');
require('./config.php');

use PolkurierWebServiceApi\Auth;
use PolkurierWebServiceApi\Config;
use PolkurierWebServiceApi\Exception\ErrorException;
use PolkurierWebServiceApi\Methods\GetOrders;
use PolkurierWebServiceApi\PolkurierWebService;

try {

    $config = new Config();
    $config->setAuthLogin(API_LOGIN);
    $config->setAuthToken(API_TOKEN);
    $config->setApiUrl('http://api.polkurier.local');

    $auth = new Auth($config);
    $webApi = new PolkurierWebService($auth, $config);

    $method = new GetOrders();
//    $method->setOrderno('30692-1142');
//    $method->setPageSize(10);
    $method->setFiles(true);
    $method->setItems(true);
    $method->setPacks(true);
    $webApi->requestMethod($method);
    var_dump($method->getData());
} catch (ErrorException $ex) {
    echo $ex->getMessage();
}
