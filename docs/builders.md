Builders
========

Payloads take named arguments. That works well when you have every value up front:

```php
new GetPointMessage(couriers: ['INPOST_PACZKOMAT'], functions: [PointFunctionEnum::Send], limit: 50);
```

It gets awkward when you don't, e.g. with an optional filter, a payload assembled over a few steps,
or a form where many fields are optional. With the constructor alone you end up with a stack of
ternaries feeding named arguments, or an untyped `$args` array spread in at the end.

For these cases the larger payloads have builders under [`Polkurier\Webservice\Builder`](../src/Builder/).
[`Builder\Entity`](../src/Builder/Entity/) mirrors the records under [`Entity`](../src/Entity/), [`Builder\Request`](../src/Builder/Request/) the messages under
[`Dto\Request`](../src/Dto/Request/), each builder in the same sub-namespace as the class it builds.

```php
use Polkurier\Webservice\Builder\Request\Courier\GetPointMessageBuilder;

$search = GetPointMessageBuilder::create()->couriers(['INPOST_PACZKOMAT']);

if ($query !== null) {
    $search->searchQuery($query);
} else {
    $search->limit(50)->page($page);
}

$points = $client->courier()->findCourierPoints($search->build());
```

How builders behave
-------------------

**Setters have the same types as the constructor.** For example
[`PackBuilder::length(int $length)`](../src/Builder/Entity/PackBuilder.php), not a generic [`AbstractBuilder::set(string, mixed)`](../src/Builder/AbstractBuilder.php).
Static analysis catches a wrong type the same way it would on the constructor.

**Fields you never set aren't passed to the constructor.** The builder stores values by name and
spreads them as named arguments, so the class's own defaults apply. The builder doesn't keep a
copy of those defaults that could go out of date.

**[`build()`](../src/Builder/) lists every missing field in one go.**

```php
Polkurier\Webservice\Entity\Order\PackItem cannot be built without width, height, weight.
```

The constructor on its own would only report the first missing argument. The exception thrown is
[`Exception\InvalidArgumentException`](../src/Exception/InvalidArgumentException.php).

What builders do not do
-----------------------

**They don't validate.** The builder checks nothing. The payload's rules run when it's sent, and
the checks are the same whether you used a builder or the constructor. See
[Validation](validation.md).

Which payloads have one
-----------------------

Every payload with four or more fields has a builder, named after the class it builds with
`Builder` appended: [`Builder\Entity\OrderBuilder`](../src/Builder/Entity/OrderBuilder.php) builds [`Entity\Order`](../src/Entity/Order.php), and
[`Builder\Request\Order\GetOrdersMessageBuilder`](../src/Builder/Request/Order/GetOrdersMessageBuilder.php) builds [`Dto\Request\Order\GetOrdersMessage`](../src/Dto/Request/Order/GetOrdersMessage.php).

A message that wraps a record takes the record as one value, so its builder has one setter for it:
[`SaveBankAccountMessageBuilder::bankAccount()`](../src/Builder/Request/BankAccount/SaveBankAccountMessageBuilder.php) takes a [`BankAccount`](../src/Entity/BankAccount.php). The two other wrappers,
[`CreateOrderMessage`](../src/Dto/Request/Order/CreateOrderMessage.php) and [`SavePhoneNumberMessage`](../src/Dto/Request/Account/SavePhoneNumberMessage.php), have no builder: [`OrderBuilder`](../src/Builder/Entity/OrderBuilder.php) and
[`PhoneNumberBuilder`](../src/Builder/Entity/PhoneNumberBuilder.php) build the record, and the wrapper's own fields go to its constructor.

Nesting builders
----------------

An order is made of several records, and you nest the builders the same way:

```php
use Polkurier\Webservice\Builder\Entity\Order\AddressBuilder;
use Polkurier\Webservice\Builder\Entity\Order\PackItemBuilder;
use Polkurier\Webservice\Builder\Entity\OrderBuilder;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;

$pack = PackItemBuilder::create()
    ->length(30)->width(20)->height(15)
    ->weight(2.5)
    ->type(PackTypeEnum::Standard)
    ->build();

$sender = AddressBuilder::create()
    ->person('Jan Kowalski')
    ->street('Długa')
    ->houseNumber('12')
    ->city('Poznań')
    ->postcode('61-001')
    ->email('jan.kowalski@example.com')
    ->phone('500600700')
    ->build();

$order = OrderBuilder::create()
    ->courier('UPS')
    ->shipmentType(ShipmentTypeEnum::Box)
    ->packs([$pack])
    ->sender($sender)
    ->recipient($recipient)
    ->build();
```

Each [`build()`](../src/Builder/) returns a complete object, and the outer builder takes it as a value. You can pass a
builder around before calling [`build()`](../src/Builder/), but the result has no link back to the builder: later
setters on the builder don't reach it.

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

A DTO built this way is readonly, so change what is sent through the builder and build again. A
record has setters, so it can also be changed after `build()`.

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
