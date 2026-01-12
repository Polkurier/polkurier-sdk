<?php
require('./config.php');
require('../autoload.php');

use PolkurierWebServiceApi\Auth;
use PolkurierWebServiceApi\Config;
use PolkurierWebServiceApi\Entities\COD;
use PolkurierWebServiceApi\Entities\Pack;
use PolkurierWebServiceApi\Entities\Recipient;
use PolkurierWebServiceApi\Entities\RodCourierService;
use PolkurierWebServiceApi\Entities\Sender;
use PolkurierWebServiceApi\Exception\ErrorException;
use PolkurierWebServiceApi\Methods\OrderValuationV2;
use PolkurierWebServiceApi\PolkurierWebService;
use PolkurierWebServiceApi\Type\PackType;
use PolkurierWebServiceApi\Type\ShipmentType;

$method = new OrderValuationV2();
$method->setShipmentType(ShipmentType::BOX);
$method->setCourier('INPOST');

$rod = new RodCourierService;
$rod->setRod(false);
$method->addCourierService($rod);
$method->setDescription('testowa paczka');

$recipient = new Recipient();
$recipient->setPerson('Jan Nowak');
$recipient->setStreet('Poznańska');
$recipient->setHouseNumber(123);
$recipient->setFlatNumber(1);
$recipient->setPostcode('60-001');
$recipient->setCity('Poznań');
$recipient->setEmail('test@test.pl');
$recipient->setPhone('987654321');
$recipient->setCountry('PL');
$method->setRecipient($recipient);

$sender = new Sender();
$sender->setPerson('Jan Kowalski');
$sender->setStreet('Kurierska');
$sender->setHouseNumber(1);
$sender->setPostcode('63-400');
$sender->setCity('Ostrów Wielkopolski');
$sender->setEmail('test@test.pl');
$sender->setPhone('123456789');
$sender->setCountry('PL');
$method->setSender($sender);

$pack = new Pack();
$pack->setWidth(10);
$pack->setHeight(10);
$pack->setLength(10);
$pack->setWeight(5);
$pack->setAmount(1);
$pack->setType(PackType::NST);
$method->addPack($pack);

$cod = new COD();
$cod->setAmount(100.56);
$method->setCod($cod);

$method->setInsurance(400);

$config = new Config();
$config->setAuthLogin(API_LOGIN);
$config->setAuthToken(API_TOKEN);

$auth = new Auth($config);
$webApi = new PolkurierWebService($auth, $config);

try {

    $webApi->requestMethod($method);
    $data = $method->getData();
    var_dump($data);

} catch (ErrorException $ex) {
    echo $ex->getMessage();
}
