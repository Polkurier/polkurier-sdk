Serialization
=============

How a PHP object becomes the API's JSON, and back. Most integrations never touch this page; it
matters when you are adding a field, debugging a payload, or putting something of your own in the
pipeline.

The hard requirement
--------------------

**PHP names must stay independent of wire names, in both directions.** A property is named for what
it means; an attribute says what the API calls it.

```php
#[Serializer\SerializedName(self::FIELD_SHIPMENT_TYPE)]   // 'shipmenttype'
private ShipmentTypeEnum $shipmentType,
```

The API's names are inconsistent enough — `codbankaccount`, `is_cover_address`, `value_nett` — that
inheriting them would be a permanent tax on every integration, and would make any future rename a
breaking release of this SDK. Any change to the serializer has to keep this property.

This is why `cuyz/valinor` was rejected when the stack was chosen: it is a mapper first, and its
renaming support does not cover serialization as well as it covers mapping. It is also half of why
`jms/serializer` was dropped — the other half is [below](#why-objects-are-built-through-their-constructors).

The stack
---------

`SerializerProvider` builds a `Symfony\Component\Serializer\Serializer` and holds it. Construction
is not cheap, so share one instance; the facade does.

```php
new Serializer(
    normalizers: [
        new BackedEnumNormalizer(),
        new AbsentDateNormalizer(),
        new DateTimeNormalizer($dateTimeContext),
        new ArrayDenormalizer(),
        new OmitEmptyNormalizer($objectNormalizer),
    ],
    encoders: [
        new JsonEncoder(new JsonEncode([JsonEncode::OPTIONS => $config->getJsonEncodeOptions()])),
    ],
);
```

**Order matters.** Symfony asks each normalizer in turn whether it supports the value, and the
first that says yes wins. `AbsentDateNormalizer` sits ahead of `DateTimeNormalizer` precisely so it
can intercept the empty string before the date parser sees it. `OmitEmptyNormalizer` is last
because it wraps the general-purpose object normalizer, which supports nearly everything.

The object normalizer is configured with four things:

| Piece | Why |
|-------|-----|
| `ClassMetadataFactory` + `AttributeLoader` | Reads `#[SerializedName]` and friends off the classes |
| `MetadataAwareNameConverter` | Resolves those names in both directions |
| `PropertyInfoExtractor` with `PhpStanExtractor` | Reads element types out of docblocks |
| `ALLOW_EXTRA_ATTRIBUTES` | On unless `rejectUnknownFields` says otherwise |

### Why `PhpStanExtractor` and not `PhpDocExtractor`

Element types for collections come from the `@var` docblock on each property — `Pack[]`,
`array<string, string>` — because PHP has no syntax for them. The SDK's properties are *promoted
constructor parameters*, and `PhpDocExtractor` does not read their docblocks. `PhpStanExtractor`
does. Swap one for the other and every list silently arrives as an array of raw arrays instead of
an array of objects.

Anything the docblock does not describe arrives as a plain array. That is the failure mode to look
for when a nested object comes back as an `array`: the docblock, not the code.

Reading responses
-----------------

`ResponseFactory` decodes the body, reads the envelope, and hands the payload to the serializer
with a format string saying what shape to expect. Five formats:

| Format | Meaning | Produced by |
|--------|---------|-------------|
| `SomeClass::class` | One object | `requestObject()`, `requestNullableObject()` |
| `SomeClass::class . '[]'` | A list of objects | `requestList()` |
| `FORMAT_STRING_LIST` | A map or list of plain strings | `requestStringMap()` |
| `FORMAT_RAW_ARRAY` | Decoded JSON, unmapped | `requestRawArray()` |
| `FORMAT_BOOLEAN` | An acknowledgement | `requestAcknowledgement()` |

The wire format is always passed as `json`, and that matters more than it sounds: JSON draws no
distinction between an integer and a float, so a whole-numbered price can arrive as an int where a
float is declared. The denormalizer widens one to the other **only when it knows the data came from
JSON**.

### Why objects are built through their constructors

A response either arrives whole, or fails immediately naming the field that was missing. There is
no third state where an object exists but a getter on it raises.

That is not free — it is why every response class has a constructor taking every field — and it is
the main reason `jms/serializer` was dropped. JMS bypassed constructors and wrote properties
directly, which left a property uninitialized whenever the API omitted a key. The object looked
fine until something read it, and then failed somewhere unrelated to the cause.

`tests/Unit/Serializer/ResponseConstructionTest.php` enforces this: every response class must either
build wholly or refuse, naming the fields it could not fill.

Empty values on the way in
--------------------------

A value that is not there can reach the SDK in more than one form, and each needs its own
handling. Two normalizers exist for this, and knowing what they do is what makes a nullable
response property make sense.

### An empty array where an object was expected

An optional nested object can arrive as an empty array rather than as null. Building that into an
object would either fail on its first required field or invent one that was never sent.

`OmitEmptyNormalizer::denormalize()` reads it as `null`:

```php
if ($data === [] && !\str_ends_with($type, '[]')) {
    return null;
}
```

The receiving property then says whether null is acceptable, which is where that decision belongs.
Where a *list* was expected, `[]` correctly means no rows.

### An empty string where a date was expected

An optional date can arrive as an empty string. `DateTimeNormalizer` refuses that — correctly, an
empty string is not a date — and fails with a message about parse formats that names neither the
field nor the reason.

`AbsentDateNormalizer` sits in front of it and answers the empty string with `null`. It claims
nothing else: a real date string falls straight through.

### An absent key

Simply missing. The constructor's default applies, which is why optional response fields carry
defaults.

Writing requests
----------------

The reverse trip is simpler, with one wrinkle.

**A field that is not sent is not the same as a field sent empty.** For the optional fields, the
two mean different things, so an optional field with nothing in it has to *disappear* from the
payload rather than serialize as null.

Nothing in `symfony/serializer` does this per field — its own skipping is all-or-nothing for null
across the whole payload. So the SDK declares it per field:

```php
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;

#[Serializer\SerializedName(self::FIELD_PICKUP)]
#[OmitWhenEmpty]
private ?PickupMessage $pickup = null,
```

`OmitEmptyNormalizer` drops those keys when the value turns out to be empty. Empty means `null`,
`''` or `[]`. **`false` and `0` are meaningful values, and are sent.**

The attribute works on properties and on methods (a field can be computed), and the hierarchy is
walked, so a base message's optional fields stay optional in its subclasses. Which fields those are
is read once per class and cached — reflecting over a payload on every request would be the most
expensive thing the SDK does.

### Dates on the way out

Each date property declares its own format, because the API uses several:

```php
#[Serializer\SerializedName(self::FIELD_DATE_FROM)]
#[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
private ?\DateTimeInterface $dateFrom = null,
```

The `timezone` setting in `SerializerConfig` affects *reading* only. Formats for writing are fixed
by the protocol.

### JSON flags

See [Configuration — jsonEncodeOptions](configuration.md#jsonencodeoptions). The one-line version:
`JSON_PRESERVE_ZERO_FRACTION` is load-bearing — without it a cash-on-delivery amount of `250.0`
goes out as `250` — and overriding the option replaces the default rather than merging with it.

The envelope
------------

Every request is the same three-field object:

```json
{
  "apimethod": "create_order",
  "authorization": {"login": "12345", "token": "…"},
  "data": { … }
}
```

`RequestMessage` is that object. Both `authorization` and `data` are marked `OmitWhenEmpty`, which
is what lets unauthenticated methods (`heartbeat`) and methods without parameters
(`get_bank_accounts`) share one shape.

Responses are the mirror:

```json
{"status": "success", "response": { … }}
{"status": "error", "response": "…", "status_code": 401, "error_code": "INVALID_AUTH_DATA"}
```

`ResponseMessage` and `ErrorMessage` are those two. `ResponseMessage` is the one part of the
envelope that is not `final`, because `ErrorMessage` extends it; nothing else should.

Extending the serializer
------------------------

Three levels, depending on how much you need to change.

### 1. Configure it

`SerializerConfig` covers metadata caching, JSON flags, timezone and unknown-field strictness. See
[Configuration](configuration.md#serializerconfig). This covers almost every real need.

### 2. Replace or add a normalizer

`SerializerProvider` is `final` — it is infrastructure, and a half-built serializer fails in ways
that look like the API misbehaving. Implement `SerializerProviderInterface` instead:

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
                new MyLenientEnumNormalizer(),   // ahead of BackedEnumNormalizer
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

Copy the SDK's normalizer list and its order as the starting point; the order is load-bearing, and
`SerializerProvider` is the reference for what it should be.

The realistic reasons to do this: tolerating an enum value the API has grown, mapping a field the
SDK does not know about yet, or instrumenting the mapping to find out where a response is losing
data.

### 3. Wrap the whole serializer

`SerializerProviderInterface` returns `SerializerInterface&DenormalizerInterface`, so a decorator
can log every payload, time it, or divert one method's responses to a recorded fixture. That last
one is what `Testing\RecordingClient` does at the transport level instead — usually the
better place, since it captures the raw body rather than the mapped result.

Debugging a mapping problem
---------------------------

Symptoms and their usual causes:

| Symptom | Look at |
|---------|---------|
| A nested object arrives as a plain `array` | The `@var` docblock on the property — the extractor reads that, not the PHP type |
| A field is always null | Its `#[SerializedName]`, against the actual wire name |
| "Failed to parse time string" | An empty-string date reaching `DateTimeNormalizer`; `AbsentDateNormalizer` should have caught it |
| A required-field error on a response | An empty array arrived where an object was expected, or a key with no default was absent |
| A float goes out as an int | `JSON_PRESERVE_ZERO_FRACTION` dropped from `jsonEncodeOptions` |
| An unexpected field breaks the call | `rejectUnknownFields: true` — intended in CI, not in production |

Two quick ways to see what actually happened:

```php
// Log the full exchange for one method.
new Config(/* … */, methodsToLog: [ApiMethodEnum::CreateOrder]);
```

```php
// Turn strictness on in a test and let an unknown field fail loudly.
new SerializerProvider(new SerializerConfig(rejectUnknownFields: true));
```
