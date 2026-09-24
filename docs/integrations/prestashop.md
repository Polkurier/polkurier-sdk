PrestaShop
==========

PrestaShop 1.7.8 and 8.x, as a module. PrestaShop 9 follows the same shape with a newer Symfony
underneath.

Autoloading
-----------

A module ships its own `vendor/`, because PrestaShop does not merge module dependencies into the
shop's own:

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

> `prepend-autoloader: false` matters. PrestaShop and its modules each have their own Composer
> tree, and prepending yours can shadow a class the core expects. Check for conflicts on the
> Symfony components in particular — the SDK needs `symfony/serializer` 7.x, which is newer than
> some PrestaShop 8 builds ship. If the shop's version is older, the safe route is
> [Mozart](https://github.com/coenjacobs/mozart)-style prefixing of the module's vendor tree at
> build time.

Configuration
-------------

Credentials go through `Configuration`, which is shop-scoped in a multistore setup:

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

            // Verify immediately rather than at the first order.
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

`testAuthApi()` takes explicit credentials rather than the configured ones, which is exactly what a
settings form needs — verify what the merchant just typed, before relying on it.

> PrestaShop stores `Configuration` values in plain text. If the shop's threat model cares, encrypt
> the token with `PrestaShop\PrestaShop\Core\Crypto\Hashing` or your own key before storing it, and
> decrypt in the client factory below.

Building the client
-------------------

PrestaShop 1.7+ has a Symfony container in the back office and not in the front office, so a small
static factory is the portable answer — it works in both, and in CLI.

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

One client per shop, one serializer for the process. The per-shop keying is what makes this correct
under multistore — a single cached client would keep whichever shop's credentials it was first
built with.

Add `_PS_CACHE_DIR_ . 'polkurier'` to whatever your deploy clears, since serializer metadata is
keyed by class rather than by version.

Symfony services, where you have a container
--------------------------------------------

For back-office controllers, `config/services.yml` in the module gives you proper injection:

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

with a matching accessor on the factory. Front-office code keeps using `Client::build()`.

Placing the shipment
--------------------

`actionValidateOrder` fires inside the order-placement transaction. **Do not call the API there** —
a slow or failing third party becomes a failed checkout. Record the intent and act later:

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

Then drain the queue from a cron controller, or from the merchant pressing a button in
`displayAdminOrderMain`. PrestaShop has no general-purpose job queue, so a table plus a cron
controller is the idiomatic answer:

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
            // Unknown outcome. Leave it queued; do not send again in this pass.
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

`formatErrors()` handles both exception types, because `ValidationException::getValidationErrors()`
and `ErrorMessage::getValidationErrors()` have the same shape — a local rejection and a server-side
one present identically.

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

Two traps. PrestaShop does not separate the house number from the street, so `address2` is whatever
the customer put there — decide on a convention and validate it at checkout, because the API needs
a number. And both phone fields are optional in PrestaShop but required by most carriers, so make
one mandatory in the address format for shipping countries you serve.

A carrier in checkout
---------------------

Register a `Carrier` on install and price it from your own rules, not from a live API call:

```php
$carrier = new Carrier();
$carrier->name = 'Polkurier';
$carrier->is_module = true;
$carrier->external_module_name = $this->name;
$carrier->shipping_external = true;
$carrier->need_range = true;
$carrier->add();
```

`getOrderShippingCost()` then runs on every cart change. Calling `orderValuation()` from it puts a
third-party round trip in the cart update, which is the single most common way a shipping module
gets a bad reputation. Price from a cached table, and reconcile against the real quote when the
merchant dispatches.

If you do quote live, cache aggressively on destination plus weight, and fail to a fixed price
rather than to an error.

Pickup point selection
----------------------

Two routes, as everywhere:

- The map widget, via `geo()->getMapToken()`, embedded in the carrier's extra content. Best UX.

Whichever you use, store the chosen point id against the cart and put it on the order address with
`OrderAddressMessageBuilder::pointId()`.

Cache the listings. There are tens of thousands of lockers, and fetching them per page load is
indistinguishable from a broken shop.

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

The controller behind that link fetches and streams the PDF:

```php
$document = Client::build()->orders()->getLabel(new GetDocumentMessage(orderNo: [$number]));

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="label.pdf"');
echo $document->getDecodedFile();
exit;
```

Multistore
----------

Everything above keys on `id_shop`: `Configuration::get()` takes it, and `Client::build()` caches
per shop. The mistake to avoid is a single static client built on first use — under multistore that
silently ships shop B's orders on shop A's account.

Debug and support
-----------------

Give the module its own log file and turn on extended logging only while diagnosing:

```php
new Config(
    // …
    methodsToLog: [\Polkurier\Webservice\Enum\Http\ApiMethodEnum::CreateOrder],
);
```

That logs full request bodies, which contain the API token. Turn it off afterwards, and keep the
file out of the web root.
