<?php
require('../autoload.php');
require('./config.php');

use PolkurierWebServiceApi\Auth;
use PolkurierWebServiceApi\Config;
use PolkurierWebServiceApi\Exception\ErrorException;
use PolkurierWebServiceApi\Methods\GetPackTemplates;
use PolkurierWebServiceApi\PolkurierWebService;

try {

    $config = new Config();
    $config->setAuthLogin(API_LOGIN);
    $config->setAuthToken(API_TOKEN);

    $auth = new Auth($config);
    $webApi = new PolkurierWebService($auth, $config);

    $method = new GetPackTemplates();
    $webApi->requestMethod($method);
    var_dump($method->getData());
} catch (ErrorException $ex) {
    echo $ex->getMessage();
}
