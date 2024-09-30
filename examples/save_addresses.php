<?php
require('../autoload.php');
require('./config.php');

use PolkurierWebServiceApi\Config;
use PolkurierWebServiceApi\Entities\Address;
use PolkurierWebServiceApi\Methods\GetAddresses;
use PolkurierWebServiceApi\Methods\SaveAddress;
use PolkurierWebServiceApi\PolkurierWebService;
use PolkurierWebServiceApi\Auth;
use PolkurierWebServiceApi\Exception\ErrorException;
use PolkurierWebServiceApi\Methods\PickupCourier;

try {

    $config = new Config();
    $config->setAuthLogin(API_LOGIN);
    $config->setAuthToken(API_TOKEN);

    $auth = new Auth($config);
    $webApi = new PolkurierWebService($auth, $config);

    $address = (new Address())
        ->setType('SENDER')
        ->setName('Testowy adres')
        ->setCompany('Firma Testowa z o.o.')
        ->setPerson('Jan Testowy')
        ->setStreet('ul. Testowa')
        ->setHouseNumber('123')
        ->setPostcode('00-001')
        ->setCity('Warszawa')
        ->setCountry('PL')
        ->setPhone('123123123')
        ->setEmail('jan.testowy@example.com');

    $method = new SaveAddress();
    $method->setAddress($address);
    $webApi->requestMethod($method);

    $savedAddress = $method->getData();
    var_dump($savedAddress);

} catch (ErrorException $ex) {
    echo $ex->getMessage();
}
