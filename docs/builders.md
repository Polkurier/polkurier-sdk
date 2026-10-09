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

Which messages have one
-----------------------

Every message with four or more fields has a builder. It is named after the message with `Builder`
appended, in the same sub-namespace under `Builder` as the message has under `Request`:
[`Builder\Order\CreateOrderMessageBuilder`](../src/Builder/Order/CreateOrderMessageBuilder.php) builds [`Request\Order\CreateOrderMessage`](../src/Request/Order/CreateOrderMessage.php).

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

This changes `$base`: setters return the same instance, not a copy.
[`AbstractBuilder::reset()`](../src/Builder/AbstractBuilder.php) clears every value set so far.

Builders in a DI container
--------------------------

A builder's constructor takes no arguments, so a DI container can create builders in place of
[`AbstractBuilder::create()`](../src/Builder/AbstractBuilder.php), e.g. through Magento's generated factories. No builder class or public builder
method is `final`, so a container can also hand out a subclass or wrap a builder in an interceptor,
e.g. a Magento plugin on a setter or on `build()`. A builder keeps its values, so a shared instance
carries them from one use to the next.

The messages themselves are readonly value objects. To change what is sent, change the values
given to the builder, or build a new message from the one `build()` returned.

Writing your own
----------------

A builder contains little more than setters and a [`build()`](../src/Builder/) method. [`AbstractBuilder`](../src/Builder/AbstractBuilder.php) does the rest: it collects values, works out which fields are required
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
