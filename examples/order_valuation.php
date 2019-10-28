<?php
require('../autoload.php');

use PolkurierWebServiceApi\Config;
use PolkurierWebServiceApi\Methods\OrderValuation;
use PolkurierWebServiceApi\Entities\Pack;
use PolkurierWebServiceApi\Auth;
use PolkurierWebServiceApi\PolkurierWebService;
use PolkurierWebServiceApi\Type\PackType;
use PolkurierWebServiceApi\Type\ShipmentType;
use PolkurierWebServiceApi\Type\ReturnCodType;
use PolkurierWebServiceApi\Type\ReturnTimeCodType;
use PolkurierWebServiceApi\Exception\ErrorException;
use PolkurierWebServiceApi\Entities\Sender;
use PolkurierWebServiceApi\Entities\Recipient;

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

    $sender = new Sender();
    $sender->setCountry('PL')
        ->setPostcode('63-400');

    $recipient = new Recipient();
    $recipient->setCountry('PL')
        ->setPostcode('63-400');

    $method
        ->setShipmentType(ShipmentType::BOX)
        ->setCOD(100)
        ->setCodtype(ReturnTimeCodType::TYPE_ST)
        ->setReturnCod(ReturnCodType::BA)
        ->setInsurance(100)
        ->setSender($sender)
        ->setRecipient($recipient);

    $webApi->requestMethod($method);
    $data = $method->getData();
    var_dump($data);
}catch (ErrorException $ex){
    echo $ex->getMessage();
}

