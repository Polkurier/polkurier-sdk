PrestaShop
==========

For PrestaShop 1.7.8 and 8.x, as a module. PrestaShop 9 works the same way on a newer Symfony
version.

Autoloading
-----------

PrestaShop doesn't merge module dependencies into the shop's own, so the module ships its own
`vendor/`:

```
modules/polkurier/
├── composer.json
├── vendor/                  ← committed, or built in CI
├── polkurier.php            ← the module class
├── config/services.yml      ← back-office services (1.7+)
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
        "php": "~8.4.0||~8.5.0",
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

> Don't drop `prepend-autoloader: false`. PrestaShop and each module have their own Composer tree,
> and if yours is prepended it can shadow a class the core expects. Check the Symfony components
> for conflicts in particular. The SDK needs `symfony/serializer` 7.x, and some PrestaShop 8 builds
> ship an older version. In that case, prefix the module's vendor tree at build time, as
> [Mozart](https://github.com/coenjacobs/mozart) does.

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
        $this->ps_versions_compliancy = ['min' => '1.7.8.0', 'max' => _PS_VERSION_];
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

`testAuthApi()` takes the credentials as an argument and ignores the configured ones. In a
settings form, that lets you check what the merchant just typed before you rely on it.

> PrestaShop stores `Configuration` values in plain text. If that's a concern for the shop, encrypt
> the token before storing it (e.g. with `sodium_crypto_secretbox()` and a key kept outside the
> database), and decrypt it in the client factory below. `Hashing` won't do: it's one-way.

Building the client
-------------------

PrestaShop 1.7+ has a Symfony container in the back office but not in the front office. A small
static factory works in both, and from the CLI too.

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

The serializer metadata cache key doesn't include a version, so have your deploy clear
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

This needs a matching static `orders()` method on `Client`. Front-office code keeps using
`Client::build()`.

Placing the shipment
--------------------

`actionValidateOrder` runs inside the order placement transaction. **Don't call the API there.** If
the API is slow or fails, the checkout fails with it. Record the order and process it later:

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

Then process the queue from a cron controller, or when the merchant clicks a button in
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
            // Unknown outcome. Leave it queued and don't send it again in this pass.
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

`formatErrors()` works for both exception types because `ValidationException::getValidationErrors()`
and `ErrorMessage::getValidationErrors()` return the same shape. The merchant sees local and API
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

Addresses need some care. PrestaShop doesn't keep the house number separate from the street, so
`address2` contains whatever the customer typed there. The API needs a number, so pick a convention
and validate it at checkout. Also, both phone fields are optional in PrestaShop, but most carriers
require a phone number. Make one of them mandatory in the address format for the countries you ship
to.

A carrier in checkout
---------------------

Register a `Carrier` on install and price it with your own rules:

```php
$carrier = new Carrier();
$carrier->name = 'Polkurier';
$carrier->is_module = true;
$carrier->external_module_name = $this->name;
$carrier->shipping_external = true;
$carrier->need_range = true;
$carrier->add();
```

PrestaShop calls `getOrderShippingCost()` on every cart change. If you call `orderValuation()` from
there, every cart update waits on a third-party request, and the shop feels slow. Price from a
cached table instead, and compare with the real quote when the merchant dispatches the order.

If you do want live quotes, cache them by destination and weight, and fall back to a fixed price if
the call fails.

Pickup point selection
----------------------

There are two options:

- The map widget, using `geo()->getMapToken()`, embedded in the carrier's extra content. This gives
  the best experience for customers.

Either way, store the selected point ID against the cart and set it on the order address with
`OrderAddressMessageBuilder::pointId()`.

Cache the lists. There are tens of thousands of lockers, and fetching them on every page load makes
the shop look broken.

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

All the code above is keyed by `id_shop`. `Configuration::get()` takes it, and `Client::build()`
caches one client per shop. Avoid a single static client built on first use. Under multistore it
quietly sends shop B's orders on shop A's account.

Debug and support
-----------------

Give the module its own log file. Turn on extended logging only while you are debugging a problem:

```php
new Config(
    // …
    methodsToLog: [\Polkurier\Webservice\Enum\Http\ApiMethodEnum::CreateOrder],
);
```

This logs full request bodies, which contain the API token. Turn it off when you're done, and keep
the log file outside the web root.
