<?php
require('../autoload.php');
require('./config.php');

use PolkurierWebServiceApi\Auth;
use PolkurierWebServiceApi\Config;
use PolkurierWebServiceApi\Entities\CourierWithLabelCourierService;
use PolkurierWebServiceApi\Entities\Pack;
use PolkurierWebServiceApi\Entities\Recipient;
use PolkurierWebServiceApi\Entities\RodCourierService;
use PolkurierWebServiceApi\Entities\Sender;
use PolkurierWebServiceApi\Entities\WeekCollectionCourierService;
use PolkurierWebServiceApi\Exception\ErrorException;
use PolkurierWebServiceApi\Methods\OrderValuation;
use PolkurierWebServiceApi\PolkurierWebService;
use PolkurierWebServiceApi\Type\PackType;
use PolkurierWebServiceApi\Type\ReturnCodType;
use PolkurierWebServiceApi\Type\ReturnTimeCodType;
use PolkurierWebServiceApi\Type\ShipmentType;

try {
    $config = new Config();
    $config->setAuthLogin(API_LOGIN);
    $config->setAuthToken(API_TOKEN);

    $auth = new Auth($config);
    $webApi = new PolkurierWebService($auth, $config);

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

    $method->addCourierService(new WeekCollectionCourierService(false));
    $method->addCourierService(new CourierWithLabelCourierService(false));
    $method->addCourierService(new RodCourierService(false));

    $webApi->requestMethod($method);
    $data = $method->getData();
    var_dump($data);

} catch (ErrorException $ex) {
    echo $ex->getMessage();
}

