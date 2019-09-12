<?php
require('../autoload.php');

use PolkurierWebServiceApi\Config;
use PolkurierWebServiceApi\Methods\OrderValuation;
use PolkurierWebServiceApi\Entities\Pack;
use PolkurierWebServiceApi\Auth;
use PolkurierWebServiceApi\PolkurierWebService;
use PolkurierWebServiceApi\Type\PackType;
use PolkurierWebServiceApi\Type\ShipmentType;
use PolkurierWebServiceApi\Exception\ErrorException;


try{
    $config = new Config();
    $auth = new Auth($config);
    $webApi = new PolkurierWebService($auth,$config);

    $method = new OrderValuation();
    $method->addPack((new Pack)
        ->setWidth(10)
        ->setHeight(20)
        ->setLength(30)
        ->setWeight(5)
        ->setAmount(1)
        ->setType(PackType::ST));

    $method
        ->setShipmentType(ShipmentType::BOX)
        ->setCOD(100)
        ->setInsurance(100);

    $webApi->requestMethod($method);
    $data = $method->getData();
    var_dump($data);
}catch (ErrorException $ex){
    echo $ex->getMessage();
}

