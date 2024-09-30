<?php
require('../autoload.php');
require('./config.php');

use PolkurierWebServiceApi\Auth;
use PolkurierWebServiceApi\Config;
use PolkurierWebServiceApi\Exception\ErrorException;
use PolkurierWebServiceApi\Methods\AvailableCarriers;
use PolkurierWebServiceApi\PolkurierWebService;

try {
    $config = new Config();
    $config->setAuthLogin(API_LOGIN);
    $config->setAuthToken(API_TOKEN);

    $auth = new Auth($config);
    $webApi = new PolkurierWebService($auth, $config);
    $method = new AvailableCarriers();
    $method->setReturncarrier('UPS');
    $method->setAdditionalData(true);
    $webApi->requestMethod($method);
    $data = $method->getData();
    var_dump($data);

} catch (ErrorException $ex) {
    echo $ex->getMessage();
}
