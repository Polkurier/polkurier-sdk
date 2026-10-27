PrestaShop
==========

For PrestaShop 9, as a module. Earlier versions don't run on PHP 8.3, which the SDK needs
([PHP compatibility](https://devdocs.prestashop-project.org/9/basics/installation/system-requirements/)).

Autoloading
-----------

PrestaShop doesn't merge module dependencies into the shop's own, so the module ships its own
`vendor/`:

```
modules/polkurier/
├── composer.json
├── vendor/                  ← committed, or built in CI
├── polkurier.php            ← the module class
├── config/services.yml      ← back-office services
├── src/
│   ├── Configuration/
│   ├── Order/
│   └── Install/
└── controllers/admin/
```

```json
{
    "name": "polkurier/prestashop",
    "require": {
        "php": "~8.3.0||~8.4.0||~8.5.0",
        "polkurier/polkurier-sdk": "^2.0"
    },
    "config": {"prepend-autoloader": false},
    "autoload": {"psr-4": {"Polkurier\\PrestaShop\\": "src/"}}
}
```

```php
// polkurier.php, near the top of the module class file
require_once __DIR__ . '/vendor/autoload.php';
```

> `prepend-autoloader: false` keeps the module's autoloader behind the shop's. PrestaShop and each
> module have their own Composer tree, and a prepended one can shadow a class the core expects. The
> Symfony components are where conflicts usually show, when the module's copy differs from the
> shop's. Prefixing the module's vendor tree at build time, as
> [Mozart](https://github.com/coenjacobs/mozart) does, avoids the clash.

Configuration
-------------

Store the credentials with `Configuration`. In a multistore setup its values are scoped per shop.

```php
<?php

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class Polkurier extends Module
{
    public const string CFG_LOGIN   = 'POLKURIER_LOGIN';
    public const string CFG_TOKEN   = 'POLKURIER_TOKEN';
    public const string CFG_SANDBOX = 'POLKURIER_SANDBOX';

    public function __construct()
    {
        $this->name = 'polkurier';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'Your company';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '9.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Polkurier', [], 'Modules.Polkurier.Admin');
        $this->description = $this->trans('Ship orders through Polkurier.pl', [], 'Modules.Polkurier.Admin');
    }

    public function install(): bool
    {
        return parent::install()
            && $this->registerHook('actionValidateOrder')
            && $this->registerHook('displayAdminOrderMain')
            && $this->installTab();
    }

    public function getContent(): string
    {
        if (Tools::isSubmit('submitPolkurier')) {
            Configuration::updateValue(self::CFG_LOGIN, Tools::getValue('login'));
            Configuration::updateValue(self::CFG_TOKEN, Tools::getValue('token'));
            Configuration::updateValue(self::CFG_SANDBOX, (bool)Tools::getValue('sandbox'));

            // Verify now, so a typo doesn't surface at the first order.
            $this->context->controller->confirmations[] = $this->verifyCredentials();
        }

        return $this->renderForm();
    }

    private function verifyCredentials(): string
    {
        $client = \Polkurier\PrestaShop\Client::build();

        try {
            $client->utils()->testAuthApi(new \Polkurier\Webservice\Http\Request\AuthorizationMessage(
                token: (string)Configuration::get(self::CFG_TOKEN),
                login: (string)Configuration::get(self::CFG_LOGIN),
            ));

            return $this->trans('Credentials accepted.', [], 'Modules.Polkurier.Admin');
        } catch (\Polkurier\Webservice\Exception\AuthorizationException) {
            return $this->trans('Credentials rejected.', [], 'Modules.Polkurier.Admin');
        } catch (\Polkurier\Webservice\Exception\ConnectionException) {
            return $this->trans('Could not reach the API.', [], 'Modules.Polkurier.Admin');
        }
    }
}
```

[`UtilsServiceInterface::testAuthApi()`](../../src/Api/Service/UtilsServiceInterface.php) takes the credentials as an argument and ignores the configured ones. In a
settings form, that lets you check what the merchant just typed before you rely on it.

> PrestaShop stores `Configuration` values in plain text. A token encrypted before it is stored
> (e.g. with [`sodium_crypto_secretbox()`](https://www.php.net/manual/en/function.sodium-crypto-secretbox.php) and a key kept outside the database) can be decrypted in the
> client factory below. `Hashing` is one-way, so it can't be used for this.

Building the client
-------------------

The full Symfony container exists only in the back office. The front office has a
[light container](https://devdocs.prestashop-project.org/9/modules/concepts/services/) holding
Doctrine and the services a module declares in `config/front/`. A small static factory works
everywhere, and from the CLI too.

```php
<?php

declare(strict_types=1);

namespace Polkurier\PrestaShop;

use Configuration;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;
use Polkurier\Webservice\PolkurierWebservice;
use Polkurier\Webservice\Serializer\SerializerConfig;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Serializer\SerializerProviderInterface;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

final class Client
{
    private static ?SerializerProviderInterface $serializer = null;

    /** @var array<int, PolkurierWebservice> keyed by shop id */
    private static array $clients = [];

    public static function build(?int $shopId = null): PolkurierWebservice
    {
        $shopId ??= (int)\Context::getContext()->shop->id;

        return self::$clients[$shopId] ??= new PolkurierWebservice(
            config: new Config(
                authorization: new AuthorizationMessage(
                    token: (string)Configuration::get(\Polkurier::CFG_TOKEN, null, null, $shopId),
                    login: (string)Configuration::get(\Polkurier::CFG_LOGIN, null, null, $shopId),
                ),
                platform: 'prestashop',
                platformVersion: _PS_VERSION_,
                sandbox: (bool)Configuration::get(\Polkurier::CFG_SANDBOX, null, null, $shopId),
            ),
            serializerProvider: self::serializer(),
        );
    }

    private static function serializer(): SerializerProviderInterface
    {
        return self::$serializer ??= new SerializerProvider(new SerializerConfig(
            metadataCache: new PhpFilesAdapter('polkurier', 0, _PS_CACHE_DIR_ . 'polkurier'),
        ));
    }
}
```

This keeps one client per shop and one serializer per process. The clients have to be keyed by
shop for multistore to work. A single cached client would keep the credentials of whichever shop it
was first built for.

The serializer metadata cache key doesn't include a version, so a deploy clears
`_PS_CACHE_DIR_ . 'polkurier'`.

Symfony services in the back office
-----------------------------------

For back-office controllers, declare services in the module's `config/services.yml` to get
constructor injection:

```yaml
services:
    _defaults:
        public: true

    Polkurier\PrestaShop\Order\ShipmentDispatcher:
        arguments:
            $orders: '@polkurier.orders'

    polkurier.orders:
        class: Polkurier\Webservice\Service\OrdersService
        factory: ['Polkurier\PrestaShop\Client', 'orders']
```

This needs a matching static `Client::orders()` method on `Client`. Front-office code keeps using
`Client::build()`.

Placing the shipment
--------------------

`actionValidateOrder` runs inside the order placement transaction. A call to the API there makes the
checkout wait on it, and fail with it. The module records the order and processes it later:

```php
public function hookActionValidateOrder(array $params): void
{
    /** @var Order $order */
    $order = $params['order'];

    Db::getInstance()->insert('polkurier_queue', [
        'id_order'   => (int)$order->id,
        'id_shop'    => (int)$order->id_shop,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}
```

The queue is then processed from a cron controller, or when the merchant clicks a button in
`displayAdminOrderMain`. PrestaShop has no general job queue, so a table and a cron controller is
the usual approach:

```php
<?php

declare(strict_types=1);

namespace Polkurier\PrestaShop\Order;

use Order;
use Polkurier\PrestaShop\Client;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

final class ShipmentDispatcher
{
    public function __construct(private readonly OrderMapper $mapper) {}

    /** @return array{0: bool, 1: string} success, and what to show the merchant */
    public function dispatch(Order $order): array
    {
        if ($this->alreadyPlaced($order)) {
            return [true, 'Already placed.'];
        }

        try {
            $created = Client::build((int)$order->id_shop)
                ->orders()
                ->createOrder($this->mapper->toMessage($order));
        } catch (ValidationException $e) {
            // Nothing was sent. Show the merchant which fields are wrong.
            return [false, $this->formatErrors($e->getValidationErrors())];
        } catch (WebserviceException $e) {
            $error = $e->getErrorMessage();

            return [false, $this->formatErrors(
                $error->getValidationErrors() ?? ['' => [$error->getResponse()]],
            )];
        } catch (ConnectionException) {
            // Unknown outcome. It stays queued and isn't sent again in this pass.
            return [false, 'Polkurier did not answer. The shipment stays queued.'];
        }

        $this->store($order, $created->getOrderNumber());

        return [true, sprintf('Shipment %s created.', $created->getOrderNumber())];
    }

    /** @param array<string, string[]> $errors */
    private function formatErrors(array $errors): string
    {
        $lines = [];

        foreach ($errors as $field => $messages) {
            foreach ($messages as $message) {
                $lines[] = trim($field . ' ' . $message);
            }
        }

        return implode('; ', $lines);
    }
}
```

`ShipmentDispatcher::formatErrors()` works for both exception types because [`ValidationException::getValidationErrors()`](../../src/Exception/ValidationException.php)
and [`ErrorMessage::getValidationErrors()`](../../src/Http/Response/ErrorMessage.php) return the same shape. The merchant sees local and API
rejections in the same format.

Mapping a PrestaShop address
----------------------------

```php
use Polkurier\Webservice\Builder\Order\OrderAddressMessageBuilder;

$address = new Address((int)$order->id_address_delivery);
$customer = new Customer((int)$order->id_customer);

$recipient = OrderAddressMessageBuilder::create()
    ->person(trim($address->firstname . ' ' . $address->lastname))
    ->street($address->address1)
    ->houseNumber($address->address2 ?: '1')
    ->city($address->city)
    ->postcode($address->postcode)
    ->phone($address->phone_mobile ?: $address->phone)
    ->email($customer->email);

if ($address->company) {
    $recipient->company($address->company);
}
```

PrestaShop doesn't keep the house number separate from the street, so `address2` contains
whatever the customer typed there, while the API needs a number. Both phone fields are optional in
PrestaShop, but most carriers require a phone number.

A carrier in checkout
---------------------

The module registers a PrestaShop [`Carrier`](https://github.com/PrestaShop/PrestaShop/blob/8.2.x/classes/Carrier.php) on install and prices it with its own rules:

```php
$carrier = new Carrier();
$carrier->name = 'Polkurier';
$carrier->is_module = true;
$carrier->external_module_name = $this->name;
$carrier->shipping_external = true;
$carrier->need_range = true;
$carrier->add();
```

PrestaShop calls [`CarrierModule::getOrderShippingCost()`](https://github.com/PrestaShop/PrestaShop/blob/8.2.x/classes/module/CarrierModule.php) on every cart change. If you call [`OrdersServiceInterface::orderValuation()`](../../src/Api/Service/OrdersServiceInterface.php) from
there, every cart update waits on a third-party request, and the shop feels slow. The example prices
from a table, and the real quote comes when the merchant dispatches the order.

Pickup point selection
----------------------

- The map widget, using [`$client->geo()->getMapToken()`](../../src/Api/Service/GeoServiceInterface.php), embedded in the carrier's extra content.
- A search with [`$client->courier()->findCourierPoints()`](../../src/Api/Service/CourierServiceInterface.php) by the customer's postcode or city, behind
  an autocomplete field.

Either way, the selected point ID is stored against the cart and set on the order address with
[`OrderAddressMessageBuilder::pointId()`](../../src/Builder/Order/OrderAddressMessageBuilder.php).

Without a search query, [`CourierServiceInterface::findCourierPoints()`](../../src/Api/Service/CourierServiceInterface.php) returns every point of a carrier,
tens of thousands for InPost, so such a list is cached instead of fetched on every page load.

Labels in the order screen
--------------------------

```php
public function hookDisplayAdminOrderMain(array $params): string
{
    $order = new Order((int)$params['id_order']);
    $number = $this->shipmentNumber($order);

    $this->context->smarty->assign([
        'polkurier_number' => $number,
        'label_url' => $this->context->link->getAdminLink('AdminPolkurierLabel', true, [], [
            'id_order' => (int)$order->id,
        ]),
    ]);

    return $this->fetch('module:polkurier/views/templates/admin/order.tpl');
}
```

The controller behind that link fetches the PDF and streams it:

```php
$document = Client::build()->orders()->getLabel(new GetDocumentMessage(orderNo: [$number]));

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="label.pdf"');
echo $document->getDecodedFile();
exit;
```

Multistore
----------

All the code above is keyed by `id_shop`. [`Configuration::get()`](https://github.com/PrestaShop/PrestaShop/blob/8.2.x/classes/Configuration.php) takes it, and `Client::build()`
caches one client per shop. A single static client built on first use would, under multistore,
quietly send shop B's orders on shop A's account.

Debug and support
-----------------

Extended logging into the module's own log file, for debugging a problem:

```php
new Config(
    // …
    methodsToLog: [\Polkurier\Webservice\Enum\Http\ApiMethodEnum::CreateOrder],
);
```

This logs full request and response bodies. The token and personal data are hidden unless you
allow them, but the bodies still describe your orders.
