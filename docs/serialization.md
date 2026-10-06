Serialization
=============

How a PHP object turns into the API's JSON and back. Most integrations can skip this page. Read it
if you are adding a field, debugging a payload or putting your own code into the pipeline.

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
release of the SDK. Any change to the serializer has to preserve this.

`cuyz/valinor` was ruled out for this reason. It is a mapper first, and its renaming support is
weaker for serialization than for mapping. It is also one of the two reasons `jms/serializer` was
dropped. The other one is covered [below](#why-objects-are-built-through-their-constructors).

Unknown enum values
-------------------

[`ResponseFactory`](../src/Http/Response/ResponseFactory.php) puts an [`UnknownEnumValues`](../src/Serializer/UnknownEnumValues.php) collector in the context when it reads a payload.
With it there, an enum value the SDK doesn't know reads as null, or is left out of a list, and is
recorded with its path. Without it, as when you call the serializer yourself, an unknown value
fails as usual. Requests are never read this way, so they stay strict.

The recorded values end up in [`ResponseWrapper::getUnknownEnumValues()`](../src/Http/Response/ResponseWrapper.php), and
[`AbstractService::logUnknownEnumValues()`](../src/Service/AbstractService.php) logs a warning for each. An empty string isn't recorded.

The services the facade builds share a [`LastResponseHolder`](../src/Http/Response/LastResponseHolder.php). Each call clears it, then stores the HTTP
response and the recorded values, which the facade returns from
[`PolkurierWebservice::getLastResponse()`](../src/PolkurierWebservice.php) and [`PolkurierWebservice::getLastUnknownEnumValues()`](../src/PolkurierWebservice.php).

The stack
---------

[`SerializerProvider`](../src/Serializer/SerializerProvider.php) builds a [`Symfony\Component\Serializer\Serializer`](https://github.com/symfony/symfony/blob/7.4/src/Symfony/Component/Serializer/Serializer.php) and keeps it. Building one
takes a while, so share a single instance. The facade already does.

```php
new Serializer(
    normalizers: [
        new SingleRowPageDenormalizer(),
        new LenientEnumDenormalizer(),
        new BackedEnumNormalizer(),
        new AbsentDateNormalizer(),
        new DateTimeNormalizer($dateTimeContext),
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
tickets is expected but a single record arrives, it reads the record as a one-row page. [`AbsentDateNormalizer`](../src/Serializer/AbsentDateNormalizer.php) comes before `DateTimeNormalizer` so it can
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

Anything the docblock does not describe arrives as a plain array. So when a nested object comes
back as an `array`, check the docblock first.

Reading responses
-----------------

[`ResponseFactory`](../src/Http/Response/ResponseFactory.php) decodes the body, reads the envelope and passes the payload to the serializer,
along with a format string that says what shape to expect. There are 5 formats:

| Format | Meaning | Produced by |
|--------|---------|-------------|
| `SomeClass::class` | One object | [`AbstractService::requestObject()`](../src/Service/AbstractService.php), [`AbstractService::requestNullableObject()`](../src/Service/AbstractService.php) |
| `SomeClass::class . '[]'` | A list of objects | [`AbstractService::requestList()`](../src/Service/AbstractService.php) |
| `FORMAT_STRING_LIST` | A map or list of plain strings | [`AbstractService::requestStringMap()`](../src/Service/AbstractService.php) |
| `FORMAT_RAW_ARRAY` | Decoded JSON, unmapped | [`AbstractService::requestRawArray()`](../src/Service/AbstractService.php) |
| `FORMAT_BOOLEAN` | An acknowledgement | [`AbstractService::requestAcknowledgement()`](../src/Service/AbstractService.php) |

The wire format is always passed as `json`. This matters because JSON does not distinguish integers
from floats, so a price with no fractional part can arrive as an int where a float is declared. The
denormalizer converts the int to a float **only when it knows the data came from JSON**.

### Why objects are built through their constructors

A response is either built in full, or it fails straight away and names the missing field. You
never get an object whose getters throw.

The cost is that every response class has a constructor that takes every field. This was the main
reason for dropping `jms/serializer`. JMS skipped constructors and wrote properties directly, so a
key missing from the response left a property uninitialized. The object looked fine until
something read that property, and then it failed somewhere far from the cause.

`tests/Unit/Serializer/ResponseConstructionTest.php` checks this. Every response class must either
build in full or refuse, and name the fields it could not fill.

Empty values on the way in
--------------------------

A missing value can reach the SDK in several forms, and each one is handled differently. Two
normalizers take care of this. Knowing what they do explains why some response properties are
nullable.

### An empty array where an object was expected

An optional nested object can arrive as an empty array instead of null. Turning that into an object
would either fail on its first required field or make up values that were never sent.

[`OmitEmptyNormalizer::denormalize()`](../src/Serializer/OmitEmptyNormalizer.php) reads it as `null`:

```php
if ($data === [] && !\str_ends_with($type, '[]')) {
    return null;
}
```

The receiving property's type then decides whether null is allowed. Where a *list* was expected,
`[]` just means no rows.

### An empty string where a date was expected

An optional date can arrive as an empty string. `DateTimeNormalizer` rejects it, since an empty
string is not a date, and its error message talks about parse formats without naming the field or
the cause.

[`AbsentDateNormalizer`](../src/Serializer/AbsentDateNormalizer.php) runs before it and turns the empty string into `null`. It handles nothing
else, so a real date string passes straight through.

### An absent key

The key is not in the response at all. The constructor's default applies, so every optional
response field has one.

Writing requests
----------------

This direction is simpler, with one catch.

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
too, so a base message's optional fields stay optional in its subclasses. The list of marked fields
is read once per class and cached, because reflecting over every payload on every request would
be slow.

### Dates on the way out

The API uses several date formats, so each date property declares its own:

```php
#[Serializer\SerializedName(self::FIELD_DATE_FROM)]
#[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
private ?\DateTimeInterface $dateFrom = null,
```

The `timezone` setting in [`SerializerConfig`](../src/Serializer/SerializerConfig.php) only affects *reading*. The formats used for writing
are fixed by the protocol.

### JSON flags

See [Configuration: jsonEncodeOptions](configuration.md#jsonencodeoptions). In short, you need
`JSON_PRESERVE_ZERO_FRACTION`. Without it a cash-on-delivery amount of `250.0` is sent as `250`.
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

[`ResponseMessage`](../src/Http/Response/ResponseMessage.php) and [`ErrorMessage`](../src/Http/Response/ErrorMessage.php) map these two. [`ResponseMessage`](../src/Http/Response/ResponseMessage.php) is the only envelope class
that is not `final`, because [`ErrorMessage`](../src/Http/Response/ErrorMessage.php) extends it. Nothing else should.

Extending the serializer
------------------------

There are 3 levels, depending on how much you need to change.

### 1. Configure it

[`SerializerConfig`](../src/Serializer/SerializerConfig.php) sets metadata caching, JSON flags, the timezone and whether unknown fields are
rejected. See [Configuration](configuration.md#serializerconfig). This is enough for most cases.

### 2. Replace or add a normalizer

You can subclass [`SerializerProvider`](../src/Serializer/SerializerProvider.php), or implement [`SerializerProviderInterface`](../src/Serializer/SerializerProviderInterface.php) to build the
serializer from scratch. Either way, test the result well: a misconfigured serializer fails in ways
that look like the API misbehaving. Implementing the interface looks like this:

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

Start from the SDK's normalizer list, in the same order. The order matters, and
[`SerializerProvider`](../src/Serializer/SerializerProvider.php) shows what it should be.

Typical reasons to do this: mapping a field the SDK does not know about yet, or instrumenting the
mapping to see where a response loses data.

### 3. Wrap the whole serializer

[`SerializerProviderInterface`](../src/Serializer/SerializerProviderInterface.php) returns `SerializerInterface&DenormalizerInterface`, so you can wrap
it in a decorator that logs or times every payload, or answers one method with a recorded fixture.
For fixtures, [`Testing\RecordingClient`](../src/Testing/RecordingClient.php) is usually the better choice. It works at the transport
level and deals with the raw body.

Debugging a mapping problem
---------------------------

Common symptoms and where to look:

| Symptom | Look at |
|---------|---------|
| A nested object arrives as a plain `array` | The `@var` docblock on the property. The extractor reads that, not the PHP type |
| A field is always null | Its `#[SerializedName]`, against the actual wire name |
| An enum field is null | A value the SDK doesn't know. [`PolkurierWebservice::getLastUnknownEnumValues()`](../src/PolkurierWebservice.php) names it |
| "Failed to parse time string" | An empty-string date reaching `DateTimeNormalizer`. [`AbsentDateNormalizer`](../src/Serializer/AbsentDateNormalizer.php) should have caught it |
| A required-field error on a response | An empty array arrived where an object was expected, or a key with no default was absent |
| A float goes out as an int | `JSON_PRESERVE_ZERO_FRACTION` dropped from `jsonEncodeOptions` |
| An unexpected field breaks the call | `rejectUnknownFields: true`. Meant for CI, not production |

Two quick ways to see what happened:

```php
// Log the full exchange for one method. Secrets, personal data and files are left out of the log
// unless you allow them, see Configuration: logging.
new Config(/* … */, methodsToLog: [ApiMethodEnum::CreateOrder]);
```

```php
// Turn strictness on in a test and let an unknown field fail loudly.
new SerializerProvider(new SerializerConfig(rejectUnknownFields: true));
```
