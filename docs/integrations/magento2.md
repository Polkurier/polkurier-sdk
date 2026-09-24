Magento 2 / Adobe Commerce
==========================

Magento 2.4.x on PHP 8.4+. The SDK goes into a module of your own; nothing here requires patching
core.

Installing the SDK into a module
--------------------------------

Magento loads the root `composer.json`'s autoloader, so the dependency goes there rather than into
the module:

```bash
composer require polkurier/polkurier-sdk
```

Declare it in your module's own `composer.json` too, so the requirement travels with the module:

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

Credentials belong in the admin, encrypted, and scoped per website — a merchant with two websites
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

`etc/config.xml` for the defaults:

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

> `type="obscure"` plus the `Encrypted` backend model is what keeps the token out of plain text in
> `core_config_data`. Read it back through `EncryptorInterface::decrypt()`, never straight from
> `ScopeConfigInterface`.

The config provider
-------------------

Because configuration is store-scoped, this is the case for a `ConfigProviderInterface` rather than
a fixed `Config`:

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

> Careful with lifetime. Magento's object manager caches shared instances for the request, and
> `AbstractService` reads its configuration once in its constructor. In a normal storefront or admin
> request that is correct — one store, one config. In a **cron job or consumer that loops over
> stores**, it is not: emulate the store *before* resolving the service, or build the client per
> store. See [the multi-store note](#multi-store-and-cron) below.

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

    <!-- The services take a PSR-3 logger and a validator; give them their own log file. -->
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

The serializer and validator need small wrappers, because neither has a constructor the object
manager can satisfy on its own:

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
        // Magento's own cache is not PSR-6; a filesystem pool under var/ is the simplest
        // correct answer, and is cleared by `bin/magento cache:flush` if you point it at var/cache.
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

Add the serializer's cache directory to your deployment's clean-up, since metadata is keyed by
class rather than by version.

Mapping an order
----------------

Keep this in its own class. It is the whole integration, and it is the only part that changes when
either side moves.

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

Two Magento-specific traps. `getStreetLine(2)` is frequently empty because Magento does not split
the house number out of the street — decide on a convention and enforce it in checkout, because
the API needs the number. And `getTelephone()` is required by Magento but not validated, so expect
`ValidationException` on `phone` more than on anything else.

Placing the order from a consumer
---------------------------------

Never from an observer on `sales_order_place_after`. Queue it.

`etc/queue_topology.xml`, `etc/queue_publisher.xml`, `etc/queue_consumer.xml` per Magento's own
documentation; the consumer itself:

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

            throw $e;   // Let the consumer retry; the guard above makes that safe.
        }

        $order->setData('polkurier_number', $created->getOrderNumber());
        $this->orderRepository->save($order);
    }
}
```

Add `polkurier_number` and `polkurier_carrier` as extension attributes or as sales order columns in
`Setup/Patch/Data`; do not stuff them into `additional_information`.

A shipping carrier
------------------

To offer Polkurier rates in checkout, extend `Magento\Shipping\Model\Carrier\AbstractCarrier` and
implement `CarrierInterface`. `collectRates()` is called on every cart change, so:

- **Cache `availableCarriers()`.** It is account-specific but stable for minutes to hours.
- **Do not call `orderValuation()` per quote unless you must.** It is a real round trip against a
  third party in the middle of a cart update. If you do, cache the result keyed on the destination
  and the parcel dimensions, and give it a short timeout.
- **Fail soft.** `collectRates()` returning `false` hides your method; throwing breaks checkout.

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

`getLabel()` takes a list of order numbers and answers with one PDF, so a mass action over the
sales grid is one call rather than one per row:

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

The one thing that reliably goes wrong. A cron job iterating stores must re-resolve the client per
store, because a service caches its configuration at construction:

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

Constructing the facade directly here rather than injecting a service is deliberate: injection gets
you the object manager's cached instance, which is holding the configuration from whichever store
was current when it was first built.

Testing
-------

Override the transport in `di.xml` under `app/code/Vendor/Polkurier/etc/test/`:

```xml
<preference for="Polkurier\Webservice\Http\ClientFactoryInterface"
            type="Polkurier\Webservice\Testing\RecordingClient"/>
```

Integration tests then exercise your mapper, the serializer and the envelope without a network. See
[Testing](../testing.md).
