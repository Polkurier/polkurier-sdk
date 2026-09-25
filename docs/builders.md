Builders
========

Messages are readonly and take named arguments. That works well when you have every value up
front:

```php
new GetPointMessage(couriers: ['INPOST_PACZKOMAT'], searchQuery: 'Poznań', limit: 5);
```

It gets awkward when you don't, e.g. with an optional filter, a payload assembled over a few steps,
or a form where many fields are optional. With the constructor alone you end up with a stack of
ternaries feeding named arguments, or an untyped `$args` array spread in at the end.

For these cases the larger messages have builders under `Polkurier\Webservice\Builder`. Each
builder sits in the same sub-namespace as the message it builds.

```php
use Polkurier\Webservice\Builder\Courier\GetPointMessageBuilder;

$search = GetPointMessageBuilder::create()->couriers(['INPOST_PACZKOMAT']);

if ($query !== null) {
    $search->searchQuery($query);
}

if ($limit !== null) {
    $search->limit($limit);
}

$points = $client->courier()->findCourierPoints($search->build());
```

How builders behave
-------------------

**Setters have the same types as the constructor.** For example `length(int $length)`, not a
generic `set(string, mixed)`. Static analysis catches a wrong type the same way it would on the
constructor.

**Fields you never set aren't passed to the constructor.** The builder stores values by name and
spreads them as named arguments, so the message's own defaults apply. The builder doesn't keep a
copy of those defaults that could go out of date.

**`build()` lists every missing field in one go.**

```php
Polkurier\Webservice\Request\Order\PackMessage cannot be built without width, height, weight.
```

The constructor on its own would only report the first missing argument. The exception thrown is
`Exception\InvalidArgumentException`.

What builders do not do
-----------------------

**They don't validate.** The builder checks nothing. The message's rules run when it's sent, and
the checks are the same whether you used a builder or the constructor. See
[Validation](validation.md).

**They're optional.** You can construct every message directly, and about two-thirds of messages
have no builder.

Available builders
------------------

There are 22 builders. Every message with four or more fields has one.

| Builder | Builds |
|---------|--------|
| `Account\SavePayerAddressMessageBuilder` | `Request\Account\SavePayerAddressMessage` |
| `Account\SavePhoneNumberMessageBuilder` | `Request\Account\SavePhoneNumberMessage` |
| `Address\SaveMessageBuilder` | `Request\Address\SaveMessage` |
| `BankAccount\SaveMessageBuilder` | `Request\BankAccount\SaveMessage` |
| `Common\BasePackMessageBuilder` | `Request\Common\BasePackMessage` |
| `Courier\GetPickupTimeMessageBuilder` | `Request\Courier\GetPickupTimeMessage` |
| `Courier\GetPointMessageBuilder` | `Request\Courier\GetPointMessage` |
| `Courier\PickupMessageBuilder` | `Request\Courier\PickupMessage` |
| `MoneyBox\CreateMoneyBoxPayoutMessageBuilder` | `Request\MoneyBox\CreateMoneyBoxPayoutMessage` |
| `MoneyBox\GetMoneyBoxPayoutsMessageBuilder` | `Request\MoneyBox\GetMoneyBoxPayoutsMessage` |
| `Order\CodMessageBuilder` | `Request\Order\CodMessage` |
| `Order\CoverAddressMessageBuilder` | `Request\Order\CoverAddressMessage` |
| `Order\CreateOrderMessageBuilder` | `Request\Order\CreateOrderMessage` |
| `Order\GetOrdersMessageBuilder` | `Request\Order\GetOrdersMessage` |
| `Order\MultiPickupRequestMessageBuilder` | `Request\Order\MultiPickupRequestMessage` |
| `Order\OrderAddressMessageBuilder` | `Request\Order\OrderAddressMessage` |
| `Order\PackMessageBuilder` | `Request\Order\PackMessage` |
| `Order\PickupMessageBuilder` | `Request\Order\PickupMessage` |
| `PackTemplate\SaveMessageBuilder` | `Request\PackTemplate\SaveMessage` |
| `System\ValidateCouponMessageBuilder` | `Request\System\ValidateCouponMessage` |
| `Ticket\CreateTicketMessageBuilder` | `Request\Ticket\CreateTicketMessage` |
| `Ticket\GetTicketsMessageBuilder` | `Request\Ticket\GetTicketsMessage` |

Nesting builders
----------------

An order is made of several messages, and you nest the builders the same way:

```php
use Polkurier\Webservice\Builder\Order\CreateOrderMessageBuilder;
use Polkurier\Webservice\Builder\Order\OrderAddressMessageBuilder;
use Polkurier\Webservice\Builder\Order\PackMessageBuilder;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;

$pack = PackMessageBuilder::create()
    ->length(30)->width(20)->height(15)
    ->weight(2.5)
    ->type(PackTypeEnum::Standard)
    ->build();

$sender = OrderAddressMessageBuilder::create()
    ->person('Jan Kowalski')
    ->street('Długa')
    ->houseNumber('12')
    ->city('Poznań')
    ->postcode('61-001')
    ->email('jan.kowalski@example.com')
    ->phone('500600700')
    ->build();

$order = CreateOrderMessageBuilder::create()
    ->courier('UPS')
    ->shipmentType(ShipmentTypeEnum::Box)
    ->packs([$pack])
    ->sender($sender)
    ->recipient($recipient)
    ->build();
```

Each `build()` returns a complete, immutable message, and the outer builder takes it as a value.
You can pass a builder around before calling `build()`, but the result is an ordinary message with
no link back to the builder.

Reusing a builder
-----------------

A builder keeps its values after `build()`, so you can build several variants from one base:

```php
$base = GetOrdersMessageBuilder::create()
    ->dateFrom(new DateTimeImmutable('-30 days'))
    ->pageSize(100);

$firstPage  = $base->page(1)->build();
$secondPage = $base->page(2)->build();
```

Note: this mutates `$base`. Setters return the same instance, not a copy. If you need two
independent builders, create two.

Writing your own
----------------

Builders are hand-written, one file each, and contain little more than setters and a `build()`
method. `AbstractBuilder` does the rest: it collects values, works out which fields are required
and refuses to build without them.

```php
use Polkurier\Webservice\Builder\AbstractBuilder;

class MyMessageBuilder extends AbstractBuilder
{
    /**
     * @param string $reference required before build()
     *
     * @return static
     */
    public function reference(string $reference): static
    {
        return $this->set('reference', $reference);
    }

    public function build(): MyMessage
    {
        return $this->make(MyMessage::class);
    }
}
```

`set()` stores a value under its constructor parameter name. The name must match, since it's
spread as a named argument. `make()` reads the required parameters from the message constructor
by reflection, so it always agrees with the constructor.

Builders aren't `final`, because their setters return `static` and a subclass can add setters.
`AbstractBuilder::__construct()` **is** final, so `create()` is safe to call on a subclass.

Drift test
----------

`tests/Unit/Builder/BuilderDriftTest.php` compares every builder with its message's constructor
and checks that:

1. Every constructor parameter has a setter with a matching type.
2. No setter is left over for a parameter that no longer exists.
3. Every message with four or more fields has a builder.

If you add a field to a message and forget the builder, this test fails. If you write your own
builders, consider copying it. It's short, and it catches builders that have fallen out of sync.
