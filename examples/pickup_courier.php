<?php
require('../autoload.php');
use PolkurierWebServiceApi\Config;
use PolkurierWebServiceApi\PolkurierWebService;
use PolkurierWebServiceApi\Auth;
use PolkurierWebServiceApi\Exception\ErrorException;
use PolkurierWebServiceApi\Methods\PickupCourier;

try{

    $config = new Config();
    $auth = new Auth($config);
    $webApi = new PolkurierWebService($auth,$config);
    $method = new PickupCourier();
    $method->setPickupdate('2018-06-24');
    $method->setShipfrom('63-400');
    $method->setCourier('DPD');
    $webApi->requestMethod($method);
    var_dump($method->getData());

}catch (ErrorException $ex){
    echo $ex->getMessage();
}
