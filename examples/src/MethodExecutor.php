<?php

use PolkurierWebServiceApi\Auth;
use PolkurierWebServiceApi\Config;
use PolkurierWebServiceApi\Entities\Address;
use PolkurierWebServiceApi\Entities\BankAccount;
use PolkurierWebServiceApi\Entities\COD;
use PolkurierWebServiceApi\Entities\Pack;
use PolkurierWebServiceApi\Entities\PackTemplate;
use PolkurierWebServiceApi\Entities\Pickup;
use PolkurierWebServiceApi\Entities\Recipient;
use PolkurierWebServiceApi\Entities\RodCourierService;
use PolkurierWebServiceApi\Entities\Sender;
use PolkurierWebServiceApi\Exception\ErrorException;
use PolkurierWebServiceApi\Exception\FatalException;
use PolkurierWebServiceApi\Methods\AbstractMethod;
use PolkurierWebServiceApi\Methods\AvailableCarriers;
use PolkurierWebServiceApi\Methods\CreateMultiOrderPickup;
use PolkurierWebServiceApi\Methods\CreateOrder;
use PolkurierWebServiceApi\Methods\DeleteAddress;
use PolkurierWebServiceApi\Methods\DeleteBankAccount;
use PolkurierWebServiceApi\Methods\DeletePackTemplate;
use PolkurierWebServiceApi\Methods\GetAddresses;
use PolkurierWebServiceApi\Methods\GetBankAccounts;
use PolkurierWebServiceApi\Methods\GetCourierPickupTime;
use PolkurierWebServiceApi\Methods\GetCourierPoint;
use PolkurierWebServiceApi\Methods\GetMapToken;
use PolkurierWebServiceApi\Methods\GetOrders;
use PolkurierWebServiceApi\Methods\GetPackTemplates;
use PolkurierWebServiceApi\Methods\GetSupportedCountriesV2;
use PolkurierWebServiceApi\Methods\Heartbeat;
use PolkurierWebServiceApi\Methods\IsMultiPickupAvailable;
use PolkurierWebServiceApi\Methods\OrderValuation;
use PolkurierWebServiceApi\Methods\SaveAddress;
use PolkurierWebServiceApi\Methods\SaveBankAccount;
use PolkurierWebServiceApi\Methods\SavePackTemplate;
use PolkurierWebServiceApi\Methods\TestAuthApi;
use PolkurierWebServiceApi\PolkurierWebService;
use PolkurierWebServiceApi\Type\PackType;
use PolkurierWebServiceApi\Type\ReturnCodType;
use PolkurierWebServiceApi\Type\ReturnTimeCodType;
use PolkurierWebServiceApi\Type\ShipmentType;

require_once "../vendor/autoload.php";

class MethodExecutor
{
    /**
     * @var PolkurierWebService
     */
    private $webApi;

    public function __construct()
    {
        $this->webApi = $this->setupWebApi();
    }

    public function getAvailableCarriers()
    {
        $method = new AvailableCarriers();
        $method->setAdditionalData(true);

        return $this->executeMethod($method);
    }

    public function healthBeat()
    {
        $method = new Heartbeat();

        return $this->executeMethod($method);
    }

    public function testAuthApi()
    {
        $method = new TestAuthApi();

        return $this->executeMethod($method);
    }

    public function getSupportedCountriesV2()
    {
        $method = new GetSupportedCountriesV2();

        return $this->executeMethod($method);
    }

    /**
     * @return array
     */
    public function getCourierPickupTime()
    {
        $method = new GetCourierPickupTime();

        if (isset($_POST['courier'])) {
            $method->setCourier($_POST['courier']);
        }
        if (isset($_POST['shipFrom'])) {
            $method->setShipFrom($_POST['shipFrom']);
        }
        if (isset($_POST['shipTo'])) {
            $method->setShipTo($_POST['shipTo']);
        }
        if (isset($_POST['parcel'])) {
            $method->setParcel($_POST['parcel']);
        }

        return $this->executeMethod($method);
    }

    public function getCourierPoints()
    {
        $method = new GetCourierPoint();
        $method->setCouriers(['INPOST_PACZKOMAT'])
            ->setFunctions(['cod', 'collect'])
            ->setSearchquery('Kościuszki 27');

        return $this->executeMethod($method);
    }

    /**
     * @throws ErrorException
     */
    public function isMultiPickupAvailable()
    {
        $method = new IsMultiPickupAvailable();
        $method->setSenderPostCode('63-400')
            ->setShipmentType('box')
            ->setCourier('AMBRO');

        $pack = new Pack();
        $pack->setLength(10)
            ->setWidth(20)
            ->setHeight(30)
            ->setWeight(5)
            ->setAmount(1)
            ->setType('ST');

        $method->addPack($pack);

        return $this->executeMethod($method);
    }

    public function createMultiOrderPickup()
    {
        $method = new CreateMultiOrderPickup();
        $method->setPickupDate('2024-10-03')
            ->setPickupTimeFrom('10:00')
            ->setPickupTimeTo('15:00')
            ->setOrders(['75337-372']);

        return $this->executeMethod($method);
    }

    public function getAddresses()
    {
        $method = new GetAddresses();
        $method->setType('SENDER');

        return $this->executeMethod($method);
    }

    public function deleteAddress()
    {
        $method = new DeleteAddress();
        $method
            ->setId('7kI')
            ->setType('SENDER');

        return $this->executeMethod($method);
    }

    public function getBankAccounts()
    {
        $method = new GetBankAccounts();

        return $this->executeMethod($method);
    }

    public function saveBankAccount()
    {
        $bankAccount = new BankAccount();
        $bankAccount->setNumber('29 1090 2398 0000 0001 2362 7888');
        $bankAccount->setName('Moje konto bankowe JP2137');
        $bankAccount->setIsDefault(false);

        $method = new SaveBankAccount($bankAccount);

        return $this->executeMethod($method);
    }

    public function deleteBankAccount()
    {
        $method = new DeleteBankAccount();
        $method->setId('tDO');

        return $this->executeMethod($method);
    }

    public function getPackTemplates()
    {
        $method = new GetPackTemplates();

        return $this->executeMethod($method);
    }

    public function savePackTemplate()
    {
        $template = new PackTemplate();
        $template->setName('BOX')
            ->setShipmentType('box')
            ->setLength(10)
            ->setWidth(12)
            ->setHeight(8)
            ->setWeight(1)
            ->setCod(0)
            ->setInsurance(0)
            ->setDescription('Test Box')
            ->setType('ST');

        $method = new SavePackTemplate();
        $method->setTemplateData($template);

        return $this->executeMethod($method);
    }


    public function deletePackTemplate()
    {
        $method = new DeletePackTemplate();
        $method->setId('ASGB6');

        return $this->executeMethod($method);
    }

    public function getMapToken()
    {
        $method = new GetMapToken();

        return $this->executeMethod($method);
    }

    /**
     * @throws ErrorException|FatalException
     */
    public function getOrderValuation()
    {
        $method = new OrderValuation();
        $method->addPack((new Pack())
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

//        $method->addCourierService(new WeekCollectionCourierService(true));
//        $method->addCourierService(new CourierWithLabelCourierService(true));
//        $method->addCourierService(new RodCourierService(true));

        return $this->executeMethod($method);
    }

    /**
     * @throws ErrorException
     * @throws FatalException
     */
    public function getOrders($page = 1, $pagesize = 100, $status = '')
    {
        $method = new GetOrders();
        $method->setPage($page)
            ->setPageSize($pagesize)
            ->setStatus($status)
            ->setPacks(true)
            ->setItems(true)
            ->setFiles(true);

        return $this->executeMethod($method);
    }

    public function getOrderDetails()
    {
        $request = $_REQUEST;
        $orderno = '75337-372';
        $method = new GetOrders();
        $method->setOrderno($orderno);

        return $this->executeMethod($method);
    }

    public function saveAddress()
    {
        $address = new Address();
        $address
            ->setName('Domyślny')
            ->setCompany('')
            ->setPerson('Jan Kowalski')
            ->setCity('Ostrów wielkopolski')
            ->setPostcode('63-400')
            ->setEmail('test@example.com')
            ->setPhone('123456789')
            ->setCountry('PL')
            ->setProvince('')
            ->setType('SENDER')
            ->setDefault(true)
            ->setIsCoverAddress(false)
            ->setStreet('Kaliska')
            ->setHouseNumber('35A')
            ->setFlatNumber('');

        $method = new SaveAddress();
        $method->setAddressData($address);

        return $this->executeMethod($method);
    }

    /**
     * @throws FatalException
     * @throws ErrorException
     */
    public function createOrder()
    {
        $method = new CreateOrder();
        $method->setShipmentType(ShipmentType::BOX);
        $method->setCourier('AMBRO');

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

        $pickup = new Pickup();
        $pickup->setDate('2018-05-15');
        $pickup->setTimeFrom('10:00');
        $pickup->setTimeTo('14:00');

        $method->setPickup($pickup);

        $cod = new COD();
        $cod->setAmount(100);
        $cod->setBankAccount('66116022020000000249765498');
        $method->setCod($cod);

//        $method->addCourierService(new SmsNotificationRecipientCourierService(true));
//        $method->addCourierService(new SmsNotificationRecipientWithNameCourierService(true));
//        $method->addCourierService(new LabelLessCourierService(true));
//        $method->addCourierService(new HandleWithCareCourierService(true));
//        $method->addCourierService(new DeliveryToOwnHandsCourierService(true));
//        $method->addCourierService(new CheckContentCourierService(true));
//        $method->addCourierService(new PhoneNotificationRecipientCourierService(true));

        $method->setInsurance(100);

        return $this->executeMethod($method);
    }

    private function executeMethod(AbstractMethod $method)
    {
        try {
            $this->webApi->requestMethod($method);

            return $method->getData();
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    private function setupWebApi()
    {
        $config = $this->setupConfig();
        $auth = new Auth($config);

        return new PolkurierWebService($auth, $config);
    }

    private function setupConfig()
    {
        return (new Config())
            ->setAuthLogin(75337)
            ->setApiUrl('http://api.polkurier.local/')
            ->setAuthToken('06c7ae0faa6c4be79f990b0f844ee150');
    }
}