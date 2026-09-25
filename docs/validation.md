Validation
==========

Payloads are checked before they are sent. Each message class is reflected once, and a bad
payload fails locally instead of costing a round trip.

The guiding rule
----------------

> A payload the API would have accepted must never be rejected here.

Validation that is stricter than the API blocks valid calls, and the failure looks like the SDK
doing its job. So the rules follow what the API documents as required. On top of that, a few
checks catch payloads that are well-formed but can't do what you meant: a date range that runs
backwards and so matches nothing, a page size above the documented maximum, or a malformed order
number in a batch.

If the SDK ever disagrees with the API, please report it as a bug. Until it's fixed,
`validatePayloads: false` turns off every check, and [changing the rules](#changing-the-rules)
shows how to drop a single rule and keep the rest.

What is not checked
-------------------

Nothing that depends on the carrier or the account: which services a carrier sells, whether it
needs a collection date, an account's PIN locks, the required PIN length, or the minimum prepaid
top-up. You can read all of these from `available_carriers`, `get_account_info` and
`get_system_info`. They can change between SDK releases, and the API checks them itself.

If you want these checks to happen earlier, read the values and enforce them yourself:

```php
$carriers = $client->courier()->availableCarriers(new AvailableCarriersMessage(additionalData: true));
$limits   = $client->system()->getSystemInfo();
$account  = $client->account()->getAccountInfo();
```

Reading a rejection
-------------------

A rejected payload throws `ValidationException` and **nothing is sent**. That's the key
difference: a `ValidationException` means no request was made and nothing changed. A
`WebserviceException` with validation errors means the request was made and the API refused it.

```php
use Polkurier\Webservice\Exception\ValidationException;

try {
    $client->orders()->createOrder($order);
} catch (ValidationException $e) {
    foreach ($e->getValidationErrors() as $field => $messages) {
        printf("%s: %s\n", $field, implode(', ', $messages));
    }
}
```

```
sender.postcode: must be a Polish postcode in the format NN-NNN
packs[1].weight: must be greater than zero
```

Some things to note about that output:

**Field names are wire names.** You get `sender.postcode`, not `sender.getPostcode()`. They match
the API's own validation errors and the request that would have been sent, so the same code can
display both.

**Paths are full paths.** Nested objects get a prefix and list entries get an index, so you can
find `packs[1].weight` without counting through the payload.

**Everything is reported at once.** Every rule on every field runs and all findings are collected,
so you can fix the payload in one pass.

There are two ways to read the report:

```php
$e->getValidationErrors();            // array<string, string[]>: every field
$e->getFieldErrors('sender.postcode'); // string[]: one field, empty if it passed
```

Messages are written to follow a field name ("must not be empty", "must be greater than zero"),
so `"$field $message"` reads as a sentence.

How the rules are attached
--------------------------

Each rule is an attribute on the thing it describes. There's no registry that maps messages to
rules and no configuration file, so there's no second list that could get out of sync.

```php
#[Validate\InOrder(earlier: 'dateFrom', later: 'dateTo')]
#[Validate\InOrder(earlier: 'codMin', later: 'codMax')]
readonly class GetOrdersMessage implements MessageInterface
{
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_PAGE_SIZE)]
        #[Validate\IntegerRange(1, self::MAX_PAGE_SIZE)]
        private ?int $pageSize = null,
        // …
    ) {}
}
```

`AttributeValidator` reads six kinds of declaration:

| Declaration | Where | What it does |
|-------------|-------|--------------|
| A `ValueValidatorInterface` | property | Checks that one value's format |
| `#[Valid]` | property | Descend into a nested object, or into each object in a list |
| `#[Each(RuleClass::class)]` | property | Apply one rule to every entry of a list of plain values |
| A `MessageRuleInterface` | class | A rule about how two fields relate |
| `#[IgnoredWhen(...)]` | class | Skip every rule, because the block does not apply |
| `#[ValidatedBy(Validator::class)]` | class | Run a validator written as a class |

The two class-level kinds are inherited, so a rule declared on a base message applies to every
message that extends it.

Reflection runs once per class and the result is cached.

### Cross-field rules

A property attribute that names a sibling, like `#[Postcode(countryFrom: 'country')]`, links two
fields from a place that can only see one of them, and the pairing has to be kept right by hand.
Only the class sees both fields, so cross-field rules go there:

```php
#[PostcodeForCountry(postcode: 'postcode', country: 'country')]
```

When there's no sibling to read, the property-level rule works alone and defaults to Polish
postcodes.

The rules
---------

### Value rules (on a property)

| Rule | Checks |
|------|--------|
| `NotBlank` | Present, and not just whitespace |
| `NotEmptyList` | At least one non-blank entry |
| `Positive` | Above zero: dimensions, weights, quantities |
| `NonNegative` | Zero or above. Zero is meaningful for insurance and cash on delivery |
| `IntegerRange(min, max)` | A whole number within bounds, mostly for paging |
| `Email` | Permissive. Only catches what clearly isn't an address |
| `PhoneNumber` | Length only, after stripping non-digits |
| `Postcode` / `Postcode(country: 'DE')` | The NN-NNN form. Other countries' formats are skipped |
| `OrderNumber` | The documented order-number format |
| `Iban` | ISO 13616 check digits, on a full IBAN or a bare national number |

Two points explain most of this list. First, only `NotBlank` checks whether a value is present.
Format rules pass on null, so the same postcode rule can be optional in one message and required
in another. Second, `Email` and `PhoneNumber` are loose. They only reject values that are
clearly wrong, because a stricter check would refuse recipients the carrier can deliver to.

`Iban` is stricter. It fully verifies the check digits, because the field decides where collected
money is paid out and a typo would send it to the wrong account. A country that isn't in its
registry is skipped, not rejected, so an account the bank accepts isn't refused just because the
registry is behind.

### Structural rules (on a property)

`#[Valid]` descends into a value. An object is checked in place, every entry of a list is checked,
and reported fields include the path. It's a marker with no rules of its own. It isn't applied by
default, since most nested values are never sent anywhere.

`#[Each(OrderNumber::class)]` applies one rule to every entry of a list of *plain values* and
reports errors against the index:

```php
#[Validate\NotEmptyList]
#[Validate\Each(Validate\OrderNumber::class)]
private array $orderNo;
```

The rule is given by class name and built with no arguments. For a rule that needs parameters or
depends on a sibling field, write a message validator.

### Class rules

`#[RequiredWhen(field: 'twoFactorCode', property: 'default', equals: true)]`: a field becomes
required based on the value of another.

`#[InOrder(earlier: 'dateFrom', later: 'dateTo')]`: a range that must run forwards. Works for
dates and numbers. If either end is missing there's no range to check, so it passes.

`#[IgnoredWhen(property: 'amount', equals: 0.0)]`: the block doesn't apply, so none of its fields
are checked. The typical case is cash on delivery with nothing to collect. An amount of zero means
no collection, and the other fields in the block would otherwise fail their usual rules on a valid
payload.

`#[ValidatedBy(SomeValidator::class)]`: see below.

Validators written as classes
-----------------------------

Four rules need more logic than an attribute can hold:

| Validator | Enforces |
|-----------|----------|
| `CreateOrderMessageValidator` | The order rules that depend on context, not on a single value |
| `OrderAddressValidator` | Which address fields are required, given the role the address plays |
| `RegisterPaymentMessageValidator` | The amount is required for exactly one payment mode |
| `ValidateCouponMessageValidator` | Coupon-against-order means all three fields, or none |

`OrderAddressValidator` is a good example. A sender must have an email address and a recipient
doesn't have to. A recipient collecting from a pickup point needs no street address at all. Which
fields are required depends on the *role* of the address in the order, which the address itself
doesn't know.

The message names its validators, and the validator doesn't name its messages. Declaring the link
on both sides let the two disagree, and a validator attached to the wrong message would silently
stop running.

Changing the rules
------------------

There are five options, roughly from most to least drastic.

### 1. Turn everything off

```php
new Config(/* … */, validatePayloads: false);
```

The service layer then uses `NullValidator`, which accepts everything, so the code path is the
same either way. Use this when the SDK disagrees with the API and you need to ship, and report the
disagreement.

### 2. Drop one rule, keep the rest

`AttributeValidator` isn't final and its internals are `protected`. Filter what `rulesFor()`
returns:

```php
use Polkurier\Webservice\Validator\AttributeValidator;
use Polkurier\Webservice\Validator\Rule\Iban;

final class WithoutIbanCheck extends AttributeValidator
{
    protected static function rulesFor(string $class): array
    {
        $rules = parent::rulesFor($class);

        foreach ($rules['values'] as $property => $validators) {
            $rules['values'][$property] = array_values(array_filter(
                $validators,
                static fn($rule): bool => !$rule instanceof Iban,
            ));
        }

        return $rules;
    }
}
```

Filter the parent's result instead of replacing it. The cache stores the *unfiltered* reflection
result, which lets two differently configured validators run in the same process.

Then register it:

```php
use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\ValidatorRegistry;

$validator = (new ValidatorRegistry())
    ->register(MessageInterface::class, new WithoutIbanCheck());

$client = new PolkurierWebservice(config: $config, validator: $validator);
```

### 3. Add rules of your own

The registry runs every validator that applies and collects all findings. You can register
against a class or an interface. A validator registered for a base message covers every message
that extends it:

```php
use Polkurier\Webservice\Request\Order\CreateOrderMessage;
use Polkurier\Webservice\Validator\AttributeValidator;
use Polkurier\Webservice\Validator\DefaultValidatorRegistryFactory;

$validator = DefaultValidatorRegistryFactory::create()
    ->register(CreateOrderMessage::class, new MyOrderPolicy());
```

`MyOrderPolicy` extends `AbstractMessageValidator`. It collects violations instead of throwing,
so its findings are reported along with the rest:

```php
use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\AbstractMessageValidator;
use Polkurier\Webservice\Validator\MessageFields;
use Polkurier\Webservice\Validator\ViolationPool;

/** @extends AbstractMessageValidator<CreateOrderMessage> */
final class MyOrderPolicy extends AbstractMessageValidator
{
    protected function collect(MessageInterface $data, ViolationPool $violations): void
    {
        $fields = new MessageFields($data);

        if ($data->getDescription() === null) {
            $violations->add($fields->field('description'), 'is required by our own policy');
        }
    }
}
```

`ViolationPool` has `add()`, `check()` (run value validators against a value), `checkWhen()` (run
them only if a condition holds) and `mergeUnder()` (add a nested report under a prefix). Use
`MessageFields::field()` to get the wire name, so your field names match the SDK's.

### 4. Attach a rule to your own message

If you've subclassed a message, declare the rule on the subclass. The attribute engine reads
inherited declarations, and `#[ValidatedBy]` on your subclass runs in addition to anything the
parent declares.

### 5. Replace the validator

`ValidatorInterface` has one method. Implement it and pass it as `validator:`, and none of the
SDK's rules run:

```php
use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\Validation\ValidatorInterface;

final class MyValidator implements ValidatorInterface
{
    public function validate(MessageInterface $data): void
    {
        // Throw ValidationException, or don't.
    }
}
```

Where validation happens
------------------------

In `AbstractService::processRequest()`, before the request is built:

```php
if ($payload !== null && $this->config->validatePayloads()) {
    $this->validator->validate($payload);
}
```

This covers every service method that takes a payload. Methods without a message have nothing to
check. If you write your own service and pass it to the client through `services:`, it gets this
behaviour only if it extends `AbstractService`.

Testing against the rules
-------------------------

A validator is a plain object with one public method, so you can test your rules without any HTTP:

```php
$this->expectException(ValidationException::class);

(new AttributeValidator())->validate($badMessage);
```

To check the report itself and not just the exception type:

```php
try {
    (new AttributeValidator())->validate($badMessage);
    self::fail('Expected a rejection.');
} catch (ValidationException $e) {
    self::assertArrayHasKey('sender.postcode', $e->getValidationErrors());
}
```

See [Testing](testing.md) for more.
