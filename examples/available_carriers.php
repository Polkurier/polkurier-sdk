<?php
require('../autoload.php');

use PolkurierWebServiceApi\PolkurierWebService;
use PolkurierWebServiceApi\Methods\AvailableCarriers;
use PolkurierWebServiceApi\Config;
use PolkurierWebServiceApi\Auth;
use PolkurierWebServiceApi\Exception\ErrorException;

try {
    $config = new Config();
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



