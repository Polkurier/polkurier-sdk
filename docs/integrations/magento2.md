Magento 2 / Adobe Commerce
==========================

For Magento 2.4.x on PHP 8.4+. You add the SDK to a module of your own. You don't need to patch
core.

Installing the SDK into a module
--------------------------------

Magento uses the autoloader from the root `composer.json`, so require the SDK there:

```bash
composer require polkurier/polkurier-sdk
```

Also declare it in the module's own `composer.json`, so the requirement goes wherever the module
goes:

```json
{
    "name": "vendor/module-polkurier",
    "type": "magento2-module",
    "require": {
        "php": "~8.4.0||~8.5.0",
        "polkurier/polkurier-sdk": "^2.0",
        "magento/framework": "*"
    },
    "autoload": {
        "files": ["registration.php"],
        "psr-4": {"Vendor\\Polkurier\\": ""}
    }
}
```

Configuration
-------------

Store the credentials in the admin, encrypted and scoped per website. A merchant with two websites
usually has two Polkurier accounts.

`etc/adminhtml/system.xml`:

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Config:etc/system_file.xsd">
    <system>
        <section id="polkurier" translate="label" sortOrder="400" showInDefault="1" showInWebsite="1">
            <label>Polkurier</label>
            <tab>sales</tab>
            <resource>Vendor_Polkurier::config</resource>
            <group id="api" translate="label" sortOrder="10" showInDefault="1" showInWebsite="1">
                <label>API</label>
                <field id="login" translate="label" type="text" sortOrder="10" showInDefault="1" showInWebsite="1">
                    <label>Customer ID</label>
                </field>
                <field id="token" translate="label" type="obscure" sortOrder="20" showInDefault="1" showInWebsite="1">
                    <label>API token</label>
                    <backend_model>Magento\Config\Model\Config\Backend\Encrypted</backend_model>
                </field>
                <field id="sandbox" translate="label" type="select" sortOrder="30" showInDefault="1" showInWebsite="1">
                    <label>Sandbox</label>
                    <source_model>Magento\Config\Model\Config\Source\Yesno</source_model>
                </field>
                <field id="timeout" translate="label" type="text" sortOrder="40" showInDefault="1">
                    <label>Timeout (seconds)</label>
                </field>
            </group>
        </section>
    </system>
</config>
```

Defaults go in `etc/config.xml`:

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Store:etc/config.xsd">
    <default>
        <polkurier>
            <api>
                <sandbox>1</sandbox>
                <timeout>30</timeout>
            </api>
        </polkurier>
    </default>
</config>
```

> With `type="obscure"` and the `Encrypted` backend model, the token isn't stored as plain text in
> `core_config_data`. Always read it back through
> [`Magento\Framework\Encryption\EncryptorInterface::decrypt()`](https://github.com/magento/magento2/blob/2.4-develop/lib/internal/Magento/Framework/Encryption/EncryptorInterface.php). The raw value
> from [`Magento\Framework\App\Config\ScopeConfigInterface`](https://github.com/magento/magento2/blob/2.4-develop/lib/internal/Magento/Framework/App/Config/ScopeConfigInterface.php) is still encrypted.

The config provider
-------------------

The configuration is scoped per store, so implement [`ConfigProviderInterface`](../../src/Api/ConfigProviderInterface.php) instead of using a
fixed [`Config`](../../src/Config.php):

```php
<?php
declare(strict_types=1);

namespace Vendor\Polkurier\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Polkurier\Webservice\Api\ConfigInterface;
use Polkurier\Webservice\Api\ConfigProviderInterface;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;

class ConfigProvider implements ConfigProviderInterface
{
    private const string XML_PATH = 'polkurier/api/';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor,
        private readonly StoreManagerInterface $storeManager,
        private readonly ModuleListInterface $moduleList,
    ) {}

    public function getConfig(): ConfigInterface
    {
        $scope = ScopeInterface::SCOPE_WEBSITE;
        $code  = $this->storeManager->getStore()->getWebsiteId();

        return new Config(
            authorization: new AuthorizationMessage(
                token: $this->encryptor->decrypt(
                    (string)$this->scopeConfig->getValue(self::XML_PATH . 'token', $scope, $code),
                ),
                login: (string)$this->scopeConfig->getValue(self::XML_PATH . 'login', $scope, $code),
            ),
            platform: 'magento2',
            platformVersion: (string)($this->moduleList->getOne('Vendor_Polkurier')['setup_version'] ?? '1.0.0'),
            apiTimeout: (int)$this->scopeConfig->getValue(self::XML_PATH . 'timeout', $scope, $code),
            sandbox: $this->scopeConfig->isSetFlag(self::XML_PATH . 'sandbox', $scope, $code),
        );
    }
}
```

> The SDK asks the provider for its configuration on every call, and the provider above reads the
> current store. A normal storefront or admin request deals with one store, so that's fine. In a
> **cron job or consumer that loops over stores**, emulate each store before calling the SDK, or
> build the client per store. See [the multi-store note](#multi-store-and-cron) below.

`etc/di.xml`
------------

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">

    <preference for="Polkurier\Webservice\Api\ConfigProviderInterface"
                type="Vendor\Polkurier\Model\ConfigProvider"/>

    <preference for="Polkurier\Webservice\Serializer\SerializerProviderInterface"
                type="Vendor\Polkurier\Model\SerializerProvider"/>

    <preference for="Polkurier\Webservice\Http\ClientFactoryInterface"
                type="Polkurier\Webservice\Http\ClientFactory"/>
    <preference for="Polkurier\Webservice\Http\Request\RequestFactoryInterface"
                type="Polkurier\Webservice\Http\Request\RequestFactory"/>
    <preference for="Polkurier\Webservice\Http\Response\ResponseFactoryInterface"
                type="Polkurier\Webservice\Http\Response\ResponseFactory"/>

    <preference for="Polkurier\Webservice\Api\Service\OrdersServiceInterface"
                type="Polkurier\Webservice\Service\OrdersService"/>
    <preference for="Polkurier\Webservice\Api\Service\CourierServiceInterface"
                type="Polkurier\Webservice\Service\CourierService"/>

    <!-- The services take a PSR-3 logger and a validator. Give them their own log file. -->
    <type name="Polkurier\Webservice\Service\AbstractService">
        <arguments>
            <argument name="logger" xsi:type="object">Vendor\Polkurier\Model\Logger</argument>
            <argument name="validator" xsi:type="object">Vendor\Polkurier\Model\Validator</argument>
        </arguments>
    </type>

    <virtualType name="Vendor\Polkurier\Model\Logger" type="Magento\Framework\Logger\Monolog">
        <arguments>
            <argument name="name" xsi:type="string">polkurier</argument>
            <argument name="handlers" xsi:type="array">
                <item name="system" xsi:type="object">Vendor\Polkurier\Logger\Handler</item>
            </argument>
        </arguments>
    </virtualType>
</config>
```

The object manager can't build the serializer or the validator on its own, so wrap each one in a
small class:

```php
<?php
declare(strict_types=1);

namespace Vendor\Polkurier\Model;

use Magento\Framework\App\CacheInterface;
use Polkurier\Webservice\Serializer\SerializerConfig;
use Polkurier\Webservice\Serializer\SerializerProvider as SdkSerializerProvider;
use Polkurier\Webservice\Serializer\SerializerProviderInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class SerializerProvider implements SerializerProviderInterface
{
    private SerializerProviderInterface $inner;

    public function __construct()
    {
        // Magento's own cache is not PSR-6. A filesystem pool under var/ is the simplest option,
        // and `bin/magento cache:flush` clears it if you point it at var/cache.
        $this->inner = new SdkSerializerProvider(new SerializerConfig(
            metadataCache: new \Symfony\Component\Cache\Adapter\PhpFilesAdapter(
                'polkurier', 0, BP . '/var/cache/polkurier',
            ),
        ));
    }

    public function getSerializer(): SerializerInterface&DenormalizerInterface
    {
        return $this->inner->getSerializer();
    }
}
```

```php
<?php
declare(strict_types=1);

namespace Vendor\Polkurier\Model;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\Validation\ValidatorInterface;
use Polkurier\Webservice\Validator\DefaultValidatorRegistryFactory;

class Validator implements ValidatorInterface
{
    private ValidatorInterface $inner;

    public function __construct()
    {
        $this->inner = DefaultValidatorRegistryFactory::create();
    }

    public function validate(MessageInterface $data): void
    {
        $this->inner->validate($data);
    }
}
```

The metadata cache key doesn't include a version, so clear the serializer's cache directory as part
of each deployment.

Mapping an order
----------------

Put the mapping in its own class. Most of your integration code lives here, and it's the part you
change when either Magento or the API changes.

```php
<?php
declare(strict_types=1);

namespace Vendor\Polkurier\Model;

use Magento\Sales\Api\Data\OrderInterface;
use Polkurier\Webservice\Builder\Order\CreateOrderMessageBuilder;
use Polkurier\Webservice\Builder\Order\OrderAddressMessageBuilder;
use Polkurier\Webservice\Builder\Order\PackMessageBuilder;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Request\Order\CreateOrderMessage;

class OrderMapper
{
    public function toMessage(OrderInterface $order, string $carrierCode): CreateOrderMessage
    {
        $shipping = $order->getShippingAddress();

        $recipient = OrderAddressMessageBuilder::create()
            ->person(trim($shipping->getFirstname() . ' ' . $shipping->getLastname()))
            ->street($shipping->getStreetLine(1))
            ->houseNumber($shipping->getStreetLine(2) ?: '1')
            ->city($shipping->getCity())
            ->postcode($shipping->getPostcode())
            ->phone($shipping->getTelephone())
            ->email((string)$order->getCustomerEmail());

        if ($shipping->getCompany()) {
            $recipient->company($shipping->getCompany());
        }

        return CreateOrderMessageBuilder::create()
            ->courier($carrierCode)
            ->shipmentType(ShipmentTypeEnum::Box)
            ->packs([$this->pack($order)])
            ->sender($this->warehouse())
            ->recipient($recipient->build())
            ->description((string)$order->getIncrementId())
            ->build();
    }

    // … pack() from the order's total weight and your box logic, warehouse() from configuration …
}
```

Watch out for two Magento quirks.
[`Magento\Sales\Model\Order\Address::getStreetLine(2)`](https://github.com/magento/magento2/blob/2.4-develop/app/code/Magento/Sales/Model/Order/Address.php) is often empty, because Magento doesn't keep
the house number separate from the street. The API needs the number, so pick a convention and
enforce it in checkout. Also, Magento requires
[`Address::getTelephone()`](https://github.com/magento/magento2/blob/2.4-develop/app/code/Magento/Sales/Model/Order/Address.php) but doesn't validate it. Expect
more [`ValidationException`](../../src/Exception/ValidationException.php)s on `phone` than on any other field.

Placing the order from a consumer
---------------------------------

Don't call the API from an observer on `sales_order_place_after`. Queue the work instead.

Set up `etc/queue_topology.xml`, `etc/queue_publisher.xml` and `etc/queue_consumer.xml` as
described in the Magento documentation. The consumer looks like this:

```php
<?php
declare(strict_types=1);

namespace Vendor\Polkurier\Model\Consumer;

use Magento\Sales\Api\OrderRepositoryInterface;
use Polkurier\Webservice\Api\Service\OrdersServiceInterface;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;
use Psr\Log\LoggerInterface;
use Vendor\Polkurier\Model\OrderMapper;

class PlaceShipment
{
    public function __construct(
        private readonly OrdersServiceInterface $orders,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderMapper $mapper,
        private readonly LoggerInterface $logger,
    ) {}

    public function process(string $orderId): void
    {
        $order = $this->orderRepository->get((int)$orderId);

        if ($order->getData('polkurier_number')) {
            return;   // Already placed. Queue delivery is at-least-once.
        }

        try {
            $created = $this->orders->createOrder(
                $this->mapper->toMessage($order, (string)$order->getData('polkurier_carrier')),
            );
        } catch (ValidationException | WebserviceException $e) {
            // Will not succeed on retry. Record it against the order and stop.
            $order->addCommentToStatusHistory('Polkurier rejected the shipment: ' . $e->getMessage());
            $this->orderRepository->save($order);

            return;
        } catch (ConnectionException $e) {
            $this->logger->error('Polkurier unreachable', ['order' => $orderId, 'exception' => $e]);

            throw $e;   // Let the consumer retry. The guard above makes that safe.
        }

        $order->setData('polkurier_number', $created->getOrderNumber());
        $this->orderRepository->save($order);
    }
}
```

Add `polkurier_number` and `polkurier_carrier` as extension attributes or as sales order columns in
`Setup/Patch/Data`. Don't put them in `additional_information`.

A shipping carrier
------------------

To show Polkurier rates in checkout, extend [`Magento\Shipping\Model\Carrier\AbstractCarrier`](https://github.com/magento/magento2/blob/2.4-develop/app/code/Magento/Shipping/Model/Carrier/AbstractCarrier.php)
and implement [`Magento\Shipping\Model\Carrier\CarrierInterface`](https://github.com/magento/magento2/blob/2.4-develop/app/code/Magento/Shipping/Model/Carrier/CarrierInterface.php).
Magento calls [`AbstractCarrierInterface::collectRates()`](https://github.com/magento/magento2/blob/2.4-develop/app/code/Magento/Shipping/Model/Carrier/AbstractCarrierInterface.php)
on every cart change, so:

- **Cache [`CourierServiceInterface::availableCarriers()`](../../src/Api/Service/CourierServiceInterface.php).** The result depends on the account but stays the same for minutes
  to hours.
- **Avoid calling [`OrdersServiceInterface::orderValuation()`](../../src/Api/Service/OrdersServiceInterface.php) for every quote.** Each call is a request to a third party in
  the middle of a cart update. If you need it, cache the result by destination and parcel
  dimensions, and use a short timeout.
- **Return `false` on errors.** If [`collectRates()`](https://github.com/magento/magento2/blob/2.4-develop/app/code/Magento/Shipping/Model/Carrier/AbstractCarrierInterface.php) returns `false`, your method is hidden. If it
  throws, checkout breaks.

```php
public function collectRates(RateRequest $request)
{
    if (!$this->getConfigFlag('active')) {
        return false;
    }

    try {
        $carriers = $this->carrierCache->available();   // your cached availableCarriers()
    } catch (PolkurierException $e) {
        $this->_logger->error('Polkurier rates unavailable', ['exception' => $e]);

        return false;
    }

    // … build a RateResult from $carriers …
}
```

Printing labels from the admin grid
-----------------------------------

[`OrdersServiceInterface::getLabel()`](../../src/Api/Service/OrdersServiceInterface.php) accepts a list of order numbers and returns a single PDF. A mass action on the sales
grid therefore needs only one call:

```php
public function execute()
{
    $numbers = [];

    foreach ($this->filter->getCollection($this->collectionFactory->create()) as $order) {
        if ($number = $order->getData('polkurier_number')) {
            $numbers[] = $number;
        }
    }

    $document = $this->orders->getLabel(new GetDocumentMessage(orderNo: $numbers));

    return $this->fileFactory->create(
        'polkurier-labels.pdf',
        $document->getDecodedFile(),
        DirectoryList::VAR_DIR,
        'application/pdf',
    );
}
```

Multi-store and cron
--------------------

This is where most Magento integrations go wrong. A cron job that loops over stores has to make
each call inside that store's emulation. Here the client is built inside the emulation from the
store's configuration:

```php
foreach ($this->storeManager->getStores() as $store) {
    $this->emulation->startEnvironmentEmulation((int)$store->getId());

    try {
        // Build the client INSIDE the emulation, not before the loop.
        $client = new PolkurierWebservice(
            config: $this->configProvider->getConfig(),
            serializerProvider: $this->serializerProvider,   // shared: stateless and expensive
        );

        $client->orders()->getOrders($filter);
    } finally {
        $this->emulation->stopEnvironmentEmulation();
    }
}
```

An injected client works too, since the SDK asks the provider for its configuration on every call.
What matters is that the call happens while the store is emulated.

Testing
-------

Override the transport in `di.xml` under `app/code/Vendor/Polkurier/etc/test/`:

```xml
<preference for="Polkurier\Webservice\Http\ClientFactoryInterface"
            type="Polkurier\Webservice\Testing\RecordingClient"/>
```

Integration tests then run your mapper, the serializer and the envelope handling without network
access. See [Testing](../testing.md).
