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

Unknown enum values
-------------------

When the SDK reads a response, an enum value it doesn't know reads as null, or is left out of a
list, and is recorded with its path. After the call,
[`PolkurierWebservice::getLastUnknownEnumValues()`](../src/PolkurierWebservice.php) lists the recorded values, and with logging on each is logged as a
warning. An empty string isn't recorded. Requests are never read this way, so they stay strict.

This leniency needs an [`UnknownEnumValues`](../src/Serializer/UnknownEnumValues.php) collector in the denormalization context, under
[`UnknownEnumValues::CONTEXT_KEY`](../src/Serializer/UnknownEnumValues.php). Without one, as when you call the serializer yourself, an unknown
value fails as usual.

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
        new AbsentDateNormalizer(),
        new ExactDateTimeNormalizer(new DateTimeNormalizer($dateTimeContext)),
        new EnumListDenormalizer(),
        new ArrayDenormalizer(),
        new OmitEmptyNormalizer($objectNormalizer),
    ],
    encoders: [
        new JsonEncoder(new JsonEncode([JsonEncode::OPTIONS => $config->getJsonEncodeOptions()])),
    ],
);
```

**Order matters.** Symfony asks each normalizer in turn whether it supports the value, and the
first one to say yes handles it. [`SingleRowPageDenormalizer`](../src/Serializer/SingleRowPageDenormalizer.php) comes first: where a page of orders or
tickets is expected but a single record arrives, it reads the record as a one-row page. [`AbsentDateNormalizer`](../src/Serializer/AbsentDateNormalizer.php) comes before [`Symfony\Component\Serializer\Normalizer\DateTimeNormalizer`](https://github.com/symfony/symfony/blob/7.4/src/Symfony/Component/Serializer/Normalizer/DateTimeNormalizer.php) so it can
catch an empty string before the date parser sees it. [`LenientEnumDenormalizer`](../src/Serializer/LenientEnumDenormalizer.php) and
[`EnumListDenormalizer`](../src/Serializer/EnumListDenormalizer.php) come before the enum and array normalizers so that, when reading a
response, an enum value the SDK doesn't know reads as null or is left out of its list (see
[unknown enum values](#unknown-enum-values)). [`OmitEmptyNormalizer`](../src/Serializer/OmitEmptyNormalizer.php) goes last because it
wraps the general object normalizer, which supports nearly everything.

The object normalizer is set up with four pieces:

| Piece | Why |
|-------|-----|
| [`Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory`](https://github.com/symfony/symfony/blob/7.4/src/Symfony/Component/Serializer/Mapping/Factory/ClassMetadataFactory.php) + `AttributeLoader` | Reads `#[SerializedName]` and friends off the classes |
| `MetadataAwareNameConverter` | Resolves those names in both directions |
| `PropertyInfoExtractor` with `PhpStanExtractor` | Reads element types out of docblocks |
| `ALLOW_EXTRA_ATTRIBUTES` | On unless `rejectUnknownFields` says otherwise |

### Why `PhpStanExtractor` and not `PhpDocExtractor`

PHP has no syntax for collection element types, so they come from the `@var` docblock on each
property (`Pack[]`, `array<string, string>`). The SDK's properties are *promoted constructor
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

A response is either built in full, or it fails straight away and names the missing field. You
never get an object whose getters throw.

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

[`AbsentDateNormalizer`](../src/Serializer/AbsentDateNormalizer.php) runs before it and turns the empty string into `null`. It handles nothing
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
private ?PickupMessage $pickup = null,
```

[`OmitEmptyNormalizer`](../src/Serializer/OmitEmptyNormalizer.php) drops these keys when the value is empty. Empty means `null`, `''` or `[]`.
**`false` and `0` are real values and are sent.**

The attribute works on properties and on methods (for computed fields). Parent classes are checked
too, so a base message's optional fields stay optional in its subclasses.

### Dates on the way out

The API uses several date formats, so each date property declares its own:

```php
#[Serializer\SerializedName(self::FIELD_DATE_FROM)]
#[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
private ?\DateTimeInterface $dateFrom = null,
```

The `timezone` setting in [`SerializerConfig`](../src/Serializer/SerializerConfig.php) only affects *reading*. The formats used for writing
are fixed by the protocol.

When reading, [`ExactDateTimeNormalizer`](../src/Serializer/ExactDateTimeNormalizer.php) takes only the fields the format has and sets the others to
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
{"status": "error", "response": "…", "status_code": 401, "error_code": "INVALID_AUTH_DATA"}
```

[`ResponseMessage`](../src/Http/Response/ResponseMessage.php) and [`ErrorMessage`](../src/Http/Response/ErrorMessage.php) map these two.

Extending the serializer
------------------------

### 1. Configure it

[`SerializerConfig`](../src/Serializer/SerializerConfig.php) sets metadata caching, JSON flags, the timezone and whether unknown fields are
rejected. See [Configuration](configuration.md#serializerconfig).

### 2. Replace or add a normalizer

You can subclass [`SerializerProvider`](../src/Serializer/SerializerProvider.php), or implement [`SerializerProviderInterface`](../src/Serializer/SerializerProviderInterface.php) to build the
serializer from scratch. A misconfigured serializer fails in ways that look like the API
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
| An enum field is null | A value the SDK doesn't know. [`PolkurierWebservice::getLastUnknownEnumValues()`](../src/PolkurierWebservice.php) names it |
| "Failed to parse time string" | An empty-string date reaching the date normalizer. [`AbsentDateNormalizer`](../src/Serializer/AbsentDateNormalizer.php) should have caught it |
| A required-field error on a response | An empty array arrived where an object was expected, or a key with no default was absent |
| A float goes out as an int | `JSON_PRESERVE_ZERO_FRACTION` dropped from `jsonEncodeOptions` |
| An unexpected field breaks the call | `rejectUnknownFields: true`. Meant for CI, not production |

```php
// Log the full exchange for one method. Secrets, personal data and files are left out of the log
// unless you allow them, see Configuration: logging.
new Config(/* … */, methodsToLog: [ApiMethodEnum::CreateOrder]);
```

```php
// Turn strictness on in a test and let an unknown field fail loudly.
new SerializerProvider(new SerializerConfig(rejectUnknownFields: true));
```
