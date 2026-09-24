Builders
========

Messages are readonly and take named arguments, which reads well when every value is to hand:

```php
new GetPointMessage(couriers: ['INPOST_PACZKOMAT'], searchQuery: 'Poznań', limit: 5);
```

It reads much less well when the values are not: a filter that may or may not apply, a payload
assembled across a few steps, a form where half the fields are optional. Written with the
constructor, that becomes a pile of ternaries feeding named arguments, or a `$args` array spread in
at the end with no types on it.

So the larger messages have builders, under `Polkurier\Webservice\Builder`, mirroring the namespace
of the message they build.

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

Three properties worth knowing
------------------------------

**Setters are typed exactly as the constructor is.** `length(int $length)`, not
`set(string, mixed)`. Static analysis catches a wrong type here just as it would on the
constructor.

**A field never set is not passed to the constructor at all.** Values are held by name and spread
as named arguments, so the message's own defaults are the only defaults there are. A builder that
mirrored each default would go stale the moment a message changed one, and would then quietly send
the old value.

**`build()` names everything that is missing, at once.**

```php
Polkurier\Webservice\Request\Order\PackMessage cannot be built without width, height, weight.
```

Letting the constructor fail instead would report the first missing argument and stop, which for a
payload of a dozen fields is a dozen attempts. The exception is
`Exception\InvalidArgumentException`.

What builders do not do
-----------------------

**They do not validate.** Nothing is checked in the builder; the message's rules run when it is
sent. Both routes reach the same checks, so a builder cannot be used to sneak a payload past
validation, and a payload that fails does so at the same point either way. See
[Validation](validation.md).

**They are not required.** Every message can be constructed directly, and roughly two-thirds have
no builder at all.

The twenty-two
--------------

Builders exist for messages with four or more fields. Below that, a builder is three lines to say
one.

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

Nesting them
------------

An order is built from several messages, and the builders nest the way the messages do:

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

There is no partial-build state: each `build()` produces a finished, immutable message, and the one
above it takes it as a value. A builder can be passed around before `build()` is called, which is
the whole point — but what comes out is an ordinary message with no builder attached to it.

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

Note that this mutates `$base` — the setters return the same instance, not a copy. Where you want
two genuinely independent builders, create two.

Writing your own
----------------

Builders are hand-written, one file each, and they are almost nothing: setters, and a `build()`.
`AbstractBuilder` holds the rest — collecting values, working out which fields are required,
refusing to build without them.

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

`set()` records a value under its constructor parameter name — the name has to match, because that
is what is spread as a named argument. `make()` reads the required parameters off the message's own
constructor by reflection, so it cannot disagree with the constructor it describes.

Builders are not `final`, because their setters return `static`; a subclass adding a setter works
as expected. `AbstractBuilder::__construct()` **is** final, which is what makes `create()` safe to
call on a subclass.

Keeping them honest
-------------------

`tests/Unit/Builder/BuilderDriftTest.php` walks every builder against its message's constructor and
checks three things:

1. Every constructor parameter has a setter, with a matching type.
2. No setter outlives the parameter it sets.
3. No message with four or more fields lacks a builder.

So adding a field to a message and forgetting its builder is a failing test rather than a payload
that quietly loses it. If you write your own builders, the same test is worth copying — it is short,
and it is the only thing standing between a hand-written mirror and drift.
