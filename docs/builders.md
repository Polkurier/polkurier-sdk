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

For these cases the larger messages have builders under [`Polkurier\Webservice\Builder`](../src/Builder/). Each
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

**Setters have the same types as the constructor.** For example [`PackMessageBuilder::length(int $length)`](../src/Builder/Order/PackMessageBuilder.php), not a
generic [`AbstractBuilder::set(string, mixed)`](../src/Builder/AbstractBuilder.php). Static analysis catches a wrong type the same way it would on the
constructor.

**Fields you never set aren't passed to the constructor.** The builder stores values by name and
spreads them as named arguments, so the message's own defaults apply. The builder doesn't keep a
copy of those defaults that could go out of date.

**[`build()`](../src/Builder/) lists every missing field in one go.**

```php
Polkurier\Webservice\Request\Order\PackMessage cannot be built without width, height, weight.
```

The constructor on its own would only report the first missing argument. The exception thrown is
[`Exception\InvalidArgumentException`](../src/Exception/InvalidArgumentException.php).

What builders do not do
-----------------------

**They don't validate.** The builder checks nothing. The message's rules run when it's sent, and
the checks are the same whether you used a builder or the constructor. See
[Validation](validation.md).

**They're optional.** You can construct every message directly, and about two-thirds of messages
have no builder.

Available builders
------------------

There are 22 builders. Every message with four or more fields has one. Builders inherit like their
messages: the parcel builders share the abstract [`Common\BasePackMessageBuilder`](../src/Builder/Common/BasePackMessageBuilder.php), and
[`Courier\PickupMessageBuilder`](../src/Builder/Courier/PickupMessageBuilder.php) extends [`Courier\GetPickupTimeMessageBuilder`](../src/Builder/Courier/GetPickupTimeMessageBuilder.php).

| Builder | Builds |
|---------|--------|
| [`Account\SavePayerAddressMessageBuilder`](../src/Builder/Account/SavePayerAddressMessageBuilder.php) | [`Request\Account\SavePayerAddressMessage`](../src/Request/Account/SavePayerAddressMessage.php) |
| [`Account\SavePhoneNumberMessageBuilder`](../src/Builder/Account/SavePhoneNumberMessageBuilder.php) | [`Request\Account\SavePhoneNumberMessage`](../src/Request/Account/SavePhoneNumberMessage.php) |
| [`Address\SaveMessageBuilder`](../src/Builder/Address/SaveMessageBuilder.php) | [`Request\Address\SaveMessage`](../src/Request/Address/SaveMessage.php) |
| [`BankAccount\SaveMessageBuilder`](../src/Builder/BankAccount/SaveMessageBuilder.php) | [`Request\BankAccount\SaveMessage`](../src/Request/BankAccount/SaveMessage.php) |
| [`Contact\SaveContactFormMessageBuilder`](../src/Builder/Contact/SaveContactFormMessageBuilder.php) | [`Request\Contact\SaveContactFormMessage`](../src/Request/Contact/SaveContactFormMessage.php) |
| [`Courier\GetPickupTimeMessageBuilder`](../src/Builder/Courier/GetPickupTimeMessageBuilder.php) | [`Request\Courier\GetPickupTimeMessage`](../src/Request/Courier/GetPickupTimeMessage.php) |
| [`Courier\GetPointMessageBuilder`](../src/Builder/Courier/GetPointMessageBuilder.php) | [`Request\Courier\GetPointMessage`](../src/Request/Courier/GetPointMessage.php) |
| [`Courier\PickupMessageBuilder`](../src/Builder/Courier/PickupMessageBuilder.php) | [`Request\Courier\PickupMessage`](../src/Request/Courier/PickupMessage.php) |
| [`MoneyBox\CreateMoneyBoxPayoutMessageBuilder`](../src/Builder/MoneyBox/CreateMoneyBoxPayoutMessageBuilder.php) | [`Request\MoneyBox\CreateMoneyBoxPayoutMessage`](../src/Request/MoneyBox/CreateMoneyBoxPayoutMessage.php) |
| [`MoneyBox\GetMoneyBoxPayoutsMessageBuilder`](../src/Builder/MoneyBox/GetMoneyBoxPayoutsMessageBuilder.php) | [`Request\MoneyBox\GetMoneyBoxPayoutsMessage`](../src/Request/MoneyBox/GetMoneyBoxPayoutsMessage.php) |
| [`Order\CodMessageBuilder`](../src/Builder/Order/CodMessageBuilder.php) | [`Request\Order\CodMessage`](../src/Request/Order/CodMessage.php) |
| [`Order\CoverAddressMessageBuilder`](../src/Builder/Order/CoverAddressMessageBuilder.php) | [`Request\Order\CoverAddressMessage`](../src/Request/Order/CoverAddressMessage.php) |
| [`Order\CreateOrderMessageBuilder`](../src/Builder/Order/CreateOrderMessageBuilder.php) | [`Request\Order\CreateOrderMessage`](../src/Request/Order/CreateOrderMessage.php) |
| [`Order\GetOrdersMessageBuilder`](../src/Builder/Order/GetOrdersMessageBuilder.php) | [`Request\Order\GetOrdersMessage`](../src/Request/Order/GetOrdersMessage.php) |
| [`Order\MultiPickupRequestMessageBuilder`](../src/Builder/Order/MultiPickupRequestMessageBuilder.php) | [`Request\Order\MultiPickupRequestMessage`](../src/Request/Order/MultiPickupRequestMessage.php) |
| [`Order\OrderAddressMessageBuilder`](../src/Builder/Order/OrderAddressMessageBuilder.php) | [`Request\Order\OrderAddressMessage`](../src/Request/Order/OrderAddressMessage.php) |
| [`Order\PackMessageBuilder`](../src/Builder/Order/PackMessageBuilder.php) | [`Request\Order\PackMessage`](../src/Request/Order/PackMessage.php) |
| [`Order\PickupMessageBuilder`](../src/Builder/Order/PickupMessageBuilder.php) | [`Request\Order\PickupMessage`](../src/Request/Order/PickupMessage.php) |
| [`PackTemplate\SaveMessageBuilder`](../src/Builder/PackTemplate/SaveMessageBuilder.php) | [`Request\PackTemplate\SaveMessage`](../src/Request/PackTemplate/SaveMessage.php) |
| [`System\ValidateCouponMessageBuilder`](../src/Builder/System/ValidateCouponMessageBuilder.php) | [`Request\System\ValidateCouponMessage`](../src/Request/System/ValidateCouponMessage.php) |
| [`Ticket\CreateTicketMessageBuilder`](../src/Builder/Ticket/CreateTicketMessageBuilder.php) | [`Request\Ticket\CreateTicketMessage`](../src/Request/Ticket/CreateTicketMessage.php) |
| [`Ticket\GetTicketsMessageBuilder`](../src/Builder/Ticket/GetTicketsMessageBuilder.php) | [`Request\Ticket\GetTicketsMessage`](../src/Request/Ticket/GetTicketsMessage.php) |

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

Each [`build()`](../src/Builder/) returns a complete, immutable message, and the outer builder takes it as a value.
You can pass a builder around before calling [`build()`](../src/Builder/), but the result is an ordinary message with
no link back to the builder.

Reusing a builder
-----------------

A builder keeps its values after [`build()`](../src/Builder/), so you can build several variants from one base:

```php
$base = GetOrdersMessageBuilder::create()
    ->dateFrom(new DateTimeImmutable('-30 days'))
    ->pageSize(100);

$firstPage  = $base->page(1)->build();
$secondPage = $base->page(2)->build();
```

Note: this mutates `$base`. Setters return the same instance, not a copy. If you need two
independent builders, create two. To start over with the same builder, call
[`AbstractBuilder::reset()`](../src/Builder/AbstractBuilder.php).

Builders in a DI container
--------------------------

A builder's constructor takes no arguments, so a DI container can create builders in place of
[`AbstractBuilder::create()`](../src/Builder/AbstractBuilder.php), e.g. through Magento's generated factories. No builder class or public builder
method is `final`, so a container can also hand out a subclass or wrap a builder in an interceptor,
e.g. a Magento plugin on a setter or on `build()`. Ask the container for a new builder each time
you need one, since a builder keeps its values.

The messages themselves are readonly value objects. To change what is sent, change the values
given to the builder, or build a new message from the one `build()` returned.

Writing your own
----------------

Builders are hand-written, one file each, and contain little more than setters and a [`build()`](../src/Builder/)
method. [`AbstractBuilder`](../src/Builder/AbstractBuilder.php) does the rest: it collects values, works out which fields are required
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

[`AbstractBuilder::set()`](../src/Builder/AbstractBuilder.php) stores a value under its constructor parameter name. The name must match, since it's
spread as a named argument. [`AbstractBuilder::make()`](../src/Builder/AbstractBuilder.php) reads the required parameters from the message constructor
by reflection, so it always agrees with the constructor.

Builders aren't `final`, because their setters return `static` and a subclass can add setters.
[`AbstractBuilder::__construct()`](../src/Builder/AbstractBuilder.php) takes no arguments. A subclass that declares its own constructor must keep it
parameterless and call `parent::__construct()`, so [`AbstractBuilder::create()`](../src/Builder/AbstractBuilder.php) is safe to call on a subclass.

Drift test
----------

`tests/Unit/Builder/BuilderDriftTest.php` compares every builder with its message's constructor
and checks that:

1. Every constructor parameter has a setter with a matching type.
2. No setter is left over for a parameter that no longer exists.
3. Every message with four or more fields has a builder.

If you add a field to a message and forget the builder, this test fails. If you write your own
builders, consider copying it. It's short, and it catches builders that have fallen out of sync.
