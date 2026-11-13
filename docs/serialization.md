Serialization
=============

How a PHP object turns into the API's JSON and back.

PHP names and wire names
------------------------

**PHP names stay independent of wire names, in both directions.** A property is named for what it
means, and an attribute gives the name the API uses.

```php
#[Serializer\SerializedName(self::FIELD_SHIPMENT_TYPE)]   // 'shipmenttype'
private ShipmentTypeEnum $shipmentType,
```

The API's names are inconsistent (`codbankaccount`, `is_cover_address`, `value_nett`). Copying them
into PHP would push that onto every integration, and renaming one later would mean a breaking
release of the SDK.

Responses that differ from the SDK
----------------------------------

A response the API changed after your SDK version was released still reads, so an application
that hasn't updated the SDK keeps working:

| The response has | The SDK reads |
|------------------|---------------|
| A field the SDK doesn't know | Nothing: the field is ignored |
| A missing field without a default, or null in a field that can't be null | The field's default, or an empty value of its type: `''`, `0`, `0.0`, `false`, `[]` or null |
| A string, number or boolean of another type | The value converted, e.g. `"12"` to `12` |
| Any other value of another type | The field's default, or an empty value |
| A list item of another type | The item converted, or left out of the list |
| A date that doesn't parse | Null |
| An enum value the SDK doesn't know | Null, or nothing in a list |
| An enum value of another type | The value converted, e.g. `12` to `"12"`, or null |

With `symfony/property-info` 6.4, the items of a list of strings, numbers or booleans aren't
checked: they read as the API sent them.

An object that arrives with fields, but none of those its class declares, is a different response
altogether, so reading it fails with a
[`MalformedResponseException`](../src/Exception/MalformedResponseException.php) instead of giving an object of empty values. A
class with a single field therefore fails when that field is renamed.

A field left out that has a default is optional, so it isn't counted. Each case is recorded with
its path, the value the API sent and the type the SDK expected. The path names fields by their PHP
property names, e.g. `[0].serviceCode`, except an unknown field, which keeps the API's name, e.g.
`[0].brand_new`. After the call,
[`PolkurierWebservice::getLastResponseDrift()`](../src/PolkurierWebservice.php) lists the [`ResponseDrift`](../src/Serializer/ResponseDrift.php) entries, and with logging on each is
logged as a warning. An empty string where an enum belongs isn't recorded. Requests are never
read this way.

This needs a [`ResponseDriftCollector`](../src/Serializer/ResponseDriftCollector.php) in the denormalization context, under
[`ResponseDriftCollector::CONTEXT_KEY`](../src/Serializer/ResponseDriftCollector.php). Without one, as when you call the serializer yourself, Symfony's own
rules apply: a value of another type, or a missing field that can't be null, fails, and an unknown
field or a missing field that can be null is ignored unless `strictResponses` is on.

With `strictResponses` on, a response is read the same way, and then fails if anything other than an
unknown enum value was recorded ([`StrictResponseSerializer`](../src/Serializer/StrictResponseSerializer.php)), so strict reading rejects exactly
what lenient reading reports.

The stack
---------

[`SerializerProvider`](../src/Serializer/SerializerProvider.php) builds a [`Symfony\Component\Serializer\Serializer`](https://github.com/symfony/symfony/blob/7.4/src/Symfony/Component/Serializer/Serializer.php) and keeps it. Building one
takes a while, so the facade builds one and reuses it.

```php
new Serializer(
    normalizers: [
        new SingleRowPageDenormalizer(),
        new LenientEnumDenormalizer(),
        new BackedEnumNormalizer(),
        new AbsentDateDenormalizer(),
        new LenientDateDenormalizer(new ExactDateTimeDenormalizer(new DateTimeNormalizer($dateTimeContext))),
        new EnumListDenormalizer(),
        new LenientListDenormalizer(new ArrayDenormalizer()),
        new RequestQuirksNormalizer(
            new OmitEmptyNormalizer(new SentFieldsNormalizer(
                new ResponseQuirksDenormalizer(new WireFormatDenormalizer(
                    new LenientObjectDenormalizer($objectNormalizer, $metadata, $extractor),
                )),
            )),
        ),
    ],
    encoders: [
        new JsonEncoder(new JsonEncode([JsonEncode::OPTIONS => $config->getJsonEncodeOptions()])),
    ],
);
```

**Order matters.** Symfony asks each normalizer in turn whether it supports the value, and the
first one to say yes handles it. [`SingleRowPageDenormalizer`](../src/Serializer/SingleRowPageDenormalizer.php) comes first: where a page of orders,
tickets or payouts is expected but a single record arrives, it reads the record as a one-row page. [`AbsentDateDenormalizer`](../src/Serializer/AbsentDateDenormalizer.php) comes before [`Symfony\Component\Serializer\Normalizer\DateTimeNormalizer`](https://github.com/symfony/symfony/blob/7.4/src/Symfony/Component/Serializer/Normalizer/DateTimeNormalizer.php) so it can
catch an empty string before the date parser sees it. [`LenientEnumDenormalizer`](../src/Serializer/LenientEnumDenormalizer.php) and
[`EnumListDenormalizer`](../src/Serializer/EnumListDenormalizer.php) come before the enum and array normalizers so that, when reading a
response, an enum value the SDK doesn't know reads as null or is left out of its list.
[`RequestQuirksNormalizer`](../src/Serializer/RequestQuirksNormalizer.php) goes last because it wraps the general object normalizer, which
supports nearly everything.
[`LenientObjectDenormalizer`](../src/Serializer/LenientObjectDenormalizer.php), [`LenientListDenormalizer`](../src/Serializer/LenientListDenormalizer.php) and [`LenientDateDenormalizer`](../src/Serializer/LenientDateDenormalizer.php) read a response that differs from the SDK (see
[Responses that differ from the SDK](#responses-that-differ-from-the-sdk)). With `strictResponses` on, [`StrictResponseSerializer`](../src/Serializer/StrictResponseSerializer.php) wraps the
serializer and fails a read that recorded a difference.

Three more pieces wrap the object normalizer, two of them when reading.
[`WireFormatDenormalizer`](../src/Serializer/WireFormatDenormalizer.php) reads the fields whose wire format differs from the value the
property holds, as the property declares:
[`#[Base64]`](../src/Serializer/Attribute/Base64.php) and [`#[Base64DataUrl]`](../src/Serializer/Attribute/Base64DataUrl.php) for files, which the property holds as bytes, and
[`#[NamedValues]`](../src/Serializer/Attribute/NamedValues.php) for values keyed by name, which the property holds as a list of name and value pairs.
[`ResponseQuirksDenormalizer`](../src/Serializer/ResponseQuirksDenormalizer.php) repairs the API's known oddities, each for one class and the fields it
names: null paging numbers, null tracking texts and default flags, a contact-form field whose
`options` names a form property instead of listing choices, carrier-specific point functions,
and transport methods keyed by method. An order also takes its additional services as a map of
codes under `courierservice` but answers with their names under that key, which the SDK reads into
[`Order::getCourierServiceNames()`](../src/Entity/Order.php). A field that differs in any other way is drift.

[`RequestQuirksNormalizer`](../src/Serializer/RequestQuirksNormalizer.php) does the same for what is sent, and only `cover_address` needs it. The
block holds an [`Address`](../src/Entity/Order/Address.php) like the sender and the recipient, but `create_order` reads nine keys
from it: `company`, `person`, `street`, `housenumber`, `postcode`, `city`, `phone`, `email`, and
the flat number under `flat_number` rather than `flatnumber`. Every other key, `country` and
`point_id` among them, is dropped, because the API prints and charges for a cover address as soon
as any value in that block is non-empty.

The object normalizer is set up with five pieces:

| Piece | Why |
|-------|-----|
| [`Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory`](https://github.com/symfony/symfony/blob/7.4/src/Symfony/Component/Serializer/Mapping/Factory/ClassMetadataFactory.php) + `AttributeLoader` | Reads `#[SerializedName]` and friends off the classes |
| `MetadataAwareNameConverter` | Resolves those names in both directions |
| `PropertyInfoExtractor` with `PhpStanExtractor` | Reads element types out of docblocks |
| `ALLOW_EXTRA_ATTRIBUTES` | On unless `strictResponses` says otherwise |
| `REQUIRE_ALL_PROPERTIES` | On with `strictResponses`, so a field without a default must be present |

### Why `PhpStanExtractor` and not `PhpDocExtractor`

PHP has no syntax for collection element types, so they come from the `@var` docblock on each
property (`PackItem[]`, `array<string, string>`). The SDK's properties are *promoted constructor
parameters*, and `PhpDocExtractor` does not read their docblocks. `PhpStanExtractor` does. If you
swap one for the other, every list silently arrives as an array of raw arrays instead of objects.

Anything the docblock does not describe arrives as a plain array, so a nested object that comes
back as an `array` usually points at the docblock.

Reading responses
-----------------

[`ResponseFactory`](../src/Http/Response/ResponseFactory.php) decodes the body, reads the envelope and passes the payload to the serializer,
along with the shape the method expects.

The wire format is always passed as `json`. This matters because JSON does not distinguish integers
from floats, so a price with no fractional part can arrive as an int where a float is declared. The
denormalizer converts the int to a float **only when it knows the data came from JSON**.

### Why objects are built through their constructors

A response is built in full, so you never get an object whose getters throw. A field the response
lacks is filled as [Responses that differ from the SDK](#responses-that-differ-from-the-sdk) describes, or with `strictResponses` on,
reading fails straight away and names it.

Every response class has a constructor that takes every field. An object filled without its
constructor would leave a missing key as an uninitialised property, which fails only when
something reads it, far from the cause.

Empty values on the way in
--------------------------

### An empty array where an object was expected

An optional nested object can arrive as an empty array instead of null. Turning that into an object
would either fail on its first required field or make up values that were never sent.

[`OmitEmptyNormalizer`](../src/Serializer/OmitEmptyNormalizer.php) reads it as `null`. The receiving property's type then decides whether null is allowed. Where a *list* was expected,
`[]` just means no rows.

### An empty string where a date was expected

An optional date can arrive as an empty string. [`Symfony\Component\Serializer\Normalizer\DateTimeNormalizer`](https://github.com/symfony/symfony/blob/7.4/src/Symfony/Component/Serializer/Normalizer/DateTimeNormalizer.php) rejects it, since an empty
string is not a date, and its error message talks about parse formats without naming the field or
the cause.

[`AbsentDateDenormalizer`](../src/Serializer/AbsentDateDenormalizer.php) runs before it and turns the empty string into `null`. It handles nothing
else, so a real date string passes straight through.

### An absent key

The key is not in the response at all. The constructor's default applies, so every optional
response field has one.

Writing requests
----------------

**Leaving a field out is different from sending it empty.** For optional fields the two mean
different things, so an optional field with no value has to be *left out* of the payload. It must
not be sent as null.

`symfony/serializer` cannot do this per field. Its null skipping applies to the whole payload or
not at all. The SDK marks the fields individually instead:

```php
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;

#[Serializer\SerializedName(self::FIELD_PICKUP)]
#[OmitWhenEmpty]
private ?Pickup $pickup = null,
```

[`OmitEmptyNormalizer`](../src/Serializer/OmitEmptyNormalizer.php) drops these keys when the value is empty. Empty means `null`, `''` or `[]`.
**`false` and `0` are real values and are sent.**

The attribute works on properties and on methods (for computed fields). Parent classes are checked
too, so a base message's optional fields stay optional in its subclasses.

### Fields the API only reports

A record is one class for sending and reading, so it holds fields the API never takes. Each of
those carries [`#[ResponseOnly]`](../src/Serializer/Attribute/ResponseOnly.php), and [`OmitEmptyNormalizer`](../src/Serializer/OmitEmptyNormalizer.php) leaves it out of the payload
whatever its value:

```php
#[Serializer\SerializedName(self::FIELD_PRICE_GROSS)]
#[ResponseOnly]
private ?float $priceGross = null,
```

### Fields a subclass of yours adds

[`SentFieldsNormalizer`](../src/Serializer/SentFieldsNormalizer.php) sends what the SDK declares, plus what a subclass declares with
`#[SerializedName]`. A property of your own without it — a primary key, an ORM column, a value you
cache on the object — stays on your side:

```php
use Polkurier\Webservice\Entity\Order;
use Symfony\Component\Serializer\Attribute as Serializer;

class ShopOrder extends Order
{
    public function __construct(
        string $courier,
        public int $id,                                   // never sent
        #[Serializer\SerializedName('shop_reference')]
        public ?string $reference = null,                 // sent
    ) {
        parent::__construct(courier: $courier);   // … and the order's other arguments
    }
}
```

The payload is built from what the object normalizer can read: a public property, or a private one
with a getter. A field written with [`#[InlineFields]`](../src/Serializer/Attribute/InlineFields.php) goes out whatever its name, because its
entries are merged into the enclosing object by [`OmitEmptyNormalizer`](../src/Serializer/OmitEmptyNormalizer.php), which wraps the filter, and
their keys aren't known in advance.

The API rejects or misreads a key it doesn't know, and it prints and charges for a cover address
as soon as any value in that block is non-empty. A class of your own that inherits nothing from
the SDK is written whole, so this never touches a payload the SDK doesn't own.

### Fields written at the enclosing level

[`#[InlineFields]`](../src/Serializer/Attribute/InlineFields.php) writes a field's own entries into the object that holds it, instead of under its
key. [`CreateOrderMessage`](../src/Dto/Request/Order/CreateOrderMessage.php) uses it for the [`Order`](../src/Entity/Order.php) it carries, so the order's fields and the
request-only `coupon_code` land side by side in `data`. The same attribute carries a map whose
keys aren't known in advance, such as the answers to a contact form. An inlined entry never
replaces a key the enclosing object already writes.

### Dates on the way out

The API uses several date formats, so each date property declares its own:

```php
#[Serializer\SerializedName(self::FIELD_DATE_FROM)]
#[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
private ?\DateTimeInterface $dateFrom = null,
```

The `timezone` setting in [`SerializerConfig`](../src/Serializer/SerializerConfig.php) only affects *reading*. The formats used for writing
are fixed by the protocol.

When reading, [`ExactDateTimeDenormalizer`](../src/Serializer/ExactDateTimeDenormalizer.php) takes only the fields the format has and sets the others to
zero. A `Y-m-d` date reads as midnight, and an `H:i` time reads as that time on 1 January 1970.
Without it, PHP fills the missing fields from the moment of reading, so the same response read
twice would give different dates.

### JSON flags

See [Configuration: jsonEncodeOptions](configuration.md#jsonencodeoptions). In short, without
`JSON_PRESERVE_ZERO_FRACTION` a cash-on-delivery amount of `250.0` is sent as `250`.
Setting the option replaces the default flags. It does not merge with them.

The envelope
------------

Every request is the same object with 3 fields:

```json
{
  "apimethod": "create_order",
  "authorization": {"login": "12345", "token": "…"},
  "data": { … }
}
```

[`RequestMessage`](../src/Http/Request/RequestMessage.php) is that object. Both `authorization` and `data` are marked [`OmitWhenEmpty`](../src/Serializer/Attribute/OmitWhenEmpty.php), so
unauthenticated methods (`heartbeat`) and methods without parameters (`get_bank_accounts`) use the
same class.

Responses look like this:

```json
{"status": "success", "response": { … }}
{"status": "error", "status_code": 7, "error_code": "ERROR_AUTH_DATA", "response": "Błąd autoryzacji. Błędny login lub token"}
```

[`ResponseMessage`](../src/Dto/Response/ResponseMessage.php) and [`ErrorMessage`](../src/Dto/Response/ErrorMessage.php) map these two.

Extending the serializer
------------------------

### 1. Configure it

[`SerializerConfig`](../src/Serializer/SerializerConfig.php) sets metadata caching, JSON flags, the timezone and whether unknown fields are
rejected. See [Configuration](configuration.md#serializerconfig).

### 2. Replace or add a normalizer

Implement [`SerializerProviderInterface`](../src/Serializer/SerializerProviderInterface.php) to build the serializer from scratch. Subclassing
[`SerializerProvider`](../src/Serializer/SerializerProvider.php) works too, but its setup and the SDK's normalizers can change in any release. A misconfigured serializer fails in ways that look like the API
misbehaving. Implementing the interface looks like this:

```php
use Polkurier\Webservice\Serializer\SerializerProviderInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class MySerializerProvider implements SerializerProviderInterface
{
    private SerializerInterface&DenormalizerInterface $serializer;

    public function __construct()
    {
        $this->serializer = new Serializer(
            normalizers: [
                new MyNormalizer(),   // ahead of the SDK's own
                // … then the SDK's own list, in its own order …
            ],
            encoders: [/* … */],
        );
    }

    public function getSerializer(): SerializerInterface&DenormalizerInterface
    {
        return $this->serializer;
    }
}
```

The order of the normalizers matters. [`SerializerProvider`](../src/Serializer/SerializerProvider.php) has the SDK's list, in order.

Typical reasons to do this: mapping a field the SDK does not know about yet, or instrumenting the
mapping to see where a response loses data.

### 3. Wrap the whole serializer

[`SerializerProviderInterface`](../src/Serializer/SerializerProviderInterface.php) returns `SerializerInterface&DenormalizerInterface`, so you can wrap
it in a decorator that logs or times every payload, or answers one method with a recorded fixture.
For fixtures there is also [`Testing\RecordingClient`](../src/Testing/RecordingClient.php), which works at the transport level, with the
raw body.

Debugging a mapping problem
---------------------------

| Symptom | Look at |
|---------|---------|
| A nested object arrives as a plain `array` | The `@var` docblock on the property. The extractor reads that, not the PHP type |
| A field is always null | Its `#[SerializedName]`, against the actual wire name |
| An enum field is null | A value the SDK doesn't know. [`PolkurierWebservice::getLastResponseDrift()`](../src/PolkurierWebservice.php) names it |
| A field is empty, zero or null, or a list is short | The response lacked it or sent another type. [`PolkurierWebservice::getLastResponseDrift()`](../src/PolkurierWebservice.php) names it |
| A date error about "an empty string" | An empty-string date reaching the date normalizer. [`AbsentDateDenormalizer`](../src/Serializer/AbsentDateDenormalizer.php) should have caught it |
| A required-field error on a response | `strictResponses: true`, or your own serializer call without a [`ResponseDriftCollector`](../src/Serializer/ResponseDriftCollector.php) |
| A float goes out as an int | `JSON_PRESERVE_ZERO_FRACTION` dropped from `jsonEncodeOptions` |
| A changed response breaks the call | `strictResponses: true`. Meant for CI, not production |

```php
// Log the full exchange for one method. Secrets, personal data and files are left out of the log
// unless you allow them, see Configuration: logging.
new Config(/* … */, methodsToLog: [ApiMethodEnum::CreateOrder]);
```

```php
// Turn strictness on in a test and let a changed response fail loudly.
new SerializerProvider(new SerializerConfig(strictResponses: true));
```
