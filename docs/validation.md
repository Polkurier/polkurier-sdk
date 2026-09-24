Validation
==========

Payloads are checked before they are sent. This costs a reflection pass per message class, once,
and saves a round trip per mistake.

The guiding rule
----------------

> A payload the API would have accepted must never be rejected here.

Client-side validation stricter than the service blocks legitimate calls, and is worse than no
validation at all — because the failure looks like the SDK working correctly. So the rules mirror
what the service is documented to require, plus a small set of checks for payloads that are
well-formed but cannot produce the result you meant: a date range that runs backwards and therefore
matches nothing, a page size above the documented maximum, a malformed order number in a batch.

If the SDK ever disagrees with the service, that is a bug worth reporting.
`validatePayloads: false` turns every check off in the meantime, and
[changing the rules](#changing-the-rules) drops one without dropping the rest.

What is deliberately not checked
--------------------------------

Anything per-carrier or per-account. Which services a carrier sells, whether it needs a collection
date, what an account's PIN locks are, how long a PIN must be, what the minimum prepaid top-up is.
All of that is readable from `available_carriers`, `get_account_info` and `get_system_info`, it
changes without a release of this SDK, and the service is what enforces it.

Read them and enforce them yourself if you want the check early:

```php
$carriers = $client->courier()->availableCarriers(new AvailableCarriersMessage(additionalData: true));
$limits   = $client->system()->getSystemInfo();
$account  = $client->account()->getAccountInfo();
```

Reading a rejection
-------------------

A rejected payload raises `ValidationException` and **nothing is sent**. That is the distinction
that matters: a `ValidationException` means no request was made and nothing changed, whereas a
`WebserviceException` carrying validation errors means the request was made and refused.

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

Three things about that output:

**Field names are wire names.** `sender.postcode`, not `sender.getPostcode()`. They line up with
the API's own validation reports and with the request that would have been sent, so one piece of
code can present both.

**Paths are real paths.** Nested objects are prefixed, list entries are indexed. `packs[1].weight`
is findable without counting through a payload.

**Everything is reported at once.** Every rule on every field runs, and their findings are pooled.
Fixing a payload is one pass, not one round trip per mistake.

Two ways to read the report:

```php
$e->getValidationErrors();            // array<string, string[]> — everything
$e->getFieldErrors('sender.postcode'); // string[] — one field, empty if it passed
```

The messages are phrased to follow a field name — "must not be empty", "must be greater than
zero" — so `"$field $message"` reads as a sentence in any presentation layer.

How the rules are attached
--------------------------

Every rule lives on the thing it describes, as an attribute. There is no registry mapping messages
to rules and no configuration file, because a second list is a list that can disagree with the
first.

```php
#[Validate\ValidatedBy(CreateOrderMessageValidator::class)]
#[Validate\InOrder(earlier: 'dateFrom', later: 'dateTo')]
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

The two class-level kinds are inherited, so a base message can declare a rule governing every
message built on it.

Reflection is done once per class and cached.

### Why cross-field rules go on the class

A property attribute naming a sibling — `#[Postcode(countryFrom: 'country')]` — ties two fields
together from a place that can see only one of them, and leaves the pairing to be kept correct by
hand. The class is the only scope that sees both, so that is where those rules live:

```php
#[PostcodeForCountry(postcode: 'postcode', country: 'country')]
```

Where there is no sibling to read, the property-level rule stands alone and checks against the
country the API serves by default.

The rules
---------

### Value rules — on a property

| Rule | Checks |
|------|--------|
| `NotBlank` | Present, and not just whitespace |
| `NotEmptyList` | At least one non-blank entry |
| `Positive` | Above zero — dimensions, weights, quantities |
| `NonNegative` | Zero or above — zero carries meaning for insurance and cash on delivery |
| `IntegerRange(min, max)` | A whole number within bounds — mostly paging |
| `Email` | Deliberately permissive; catches what plainly is not an address |
| `PhoneNumber` | Length only, after stripping non-digits |
| `Postcode` / `Postcode(country: 'DE')` | The NN-NNN form; other countries' formats are passed over |
| `OrderNumber` | The documented order-number format |
| `Iban` | ISO 13616 check digits, on a full IBAN or a bare national number |

Two design notes that explain most of the list. **Absence is always `NotBlank`'s business** — a
format rule passes on null, which is what lets the same postcode rule be optional in one message
and mandatory in another. And **permissiveness is deliberate** where the cost of a false rejection
is high: `Email` and `PhoneNumber` check only what is unambiguously wrong, because a stricter rule
would refuse recipients the carrier would happily deliver to.

`Iban` is the exception, and knowingly so: it verifies the check digits properly, because the field
decides where collected money is sent and a transposed pair is a payment to a stranger. A country
outside its registry is passed over rather than rejected — countries do join, and refusing an
account the bank itself accepts is the worse failure.

### Structural rules — on a property

`#[Valid]` descends. An object is checked in place; a list has every entry checked; reported fields
carry the path. It is a marker and carries no rules of its own, and it is not the default — most
nested values are never sent anywhere.

`#[Each(OrderNumber::class)]` applies one rule to every entry of a list of *plain values*, reporting
against the index:

```php
#[Validate\NotEmptyList]
#[Validate\Each(Validate\OrderNumber::class)]
private array $orderNo;
```

The rule is named by class and built with no arguments, which is all PHP attributes allow. A
parameterised rule, or one depending on a sibling, needs a message validator.

### Class rules

`#[RequiredWhen(field: 'twoFactorCode', property: 'default', equals: true)]` — a field becomes
mandatory because another says so.

`#[InOrder(earlier: 'dateFrom', later: 'dateTo')]` — a range that has to run forwards. Works for
dates and numbers. Either end absent means there is no range to judge, so it passes.

`#[IgnoredWhen(property: 'amount', equals: 0.0)]` — the block does not apply, so nothing in it is
checked. Cash on delivery with nothing to collect is the case this exists for: an amount of zero
means there is no collection, and the fields beside it describe a collection that is not happening.
Holding them to their usual rules would reject a payload that is perfectly valid.

`#[ValidatedBy(SomeValidator::class)]` — see below.

Validators written as classes
-----------------------------

Four rules have enough logic behind them not to fit an attribute:

| Validator | Enforces |
|-----------|----------|
| `CreateOrderMessageValidator` | The order rules that depend on context rather than on a value |
| `OrderAddressValidator` | Which address fields are required, given the role the address plays |
| `RegisterPaymentMessageValidator` | The amount is required for exactly one payment mode |
| `ValidateCouponMessageValidator` | Coupon-against-order means all three fields, or none |

`OrderAddressValidator` is the clearest illustration of why these cannot be attributes. An email
address is required of a sender and optional for a recipient, and a recipient collecting from a
pickup point needs no street address at all. Which fields are required depends on the *role* the
address is playing — a fact about the order, not about the address.

A message names its validators; the validator does not name its messages. Stating it on both sides
invited them to disagree, and a validator paired with the wrong message then stopped running
without saying so.

Changing the rules
------------------

Five ways, roughly in order of how much they take away.

### 1. Turn everything off

```php
new Config(/* … */, validatePayloads: false);
```

The service layer then uses `NullValidator`, which accepts everything. One code path either way.
This is the escape hatch for "the SDK disagrees with the API and I need to ship" — report the
disagreement too.

### 2. Drop one rule, keep the rest

`AttributeValidator` is not final and its internals are `protected`. Filter what `rulesFor()`
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

Filter what the parent gives back rather than replacing it. The cache holds the *unfiltered*
reflection, which is what lets two differently configured validators coexist in one process.

Then register it:

```php
use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\ValidatorRegistry;

$validator = (new ValidatorRegistry())
    ->register(MessageInterface::class, new WithoutIbanCheck());

$client = new PolkurierWebservice(config: $config, validator: $validator);
```

### 3. Add rules of your own

The registry runs everything that applies, and pools the findings. Register against a class or an
interface — a validator registered for a base message covers every message built on it:

```php
use Polkurier\Webservice\Request\Order\CreateOrderMessage;
use Polkurier\Webservice\Validator\AttributeValidator;
use Polkurier\Webservice\Validator\DefaultValidatorRegistryFactory;

$validator = DefaultValidatorRegistryFactory::create()
    ->register(CreateOrderMessage::class, new MyOrderPolicy());
```

`MyOrderPolicy` extends `AbstractMessageValidator` and collects rather than throws, so its findings
join the rest:

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

`ViolationPool` gives you `add()`, `check()` (run value validators against a value),
`checkWhen()` (run them only when a condition holds) and `mergeUnder()` (fold a nested report in
under a prefix). Use `MessageFields::field()` to get the wire name, so your messages read the same
as the SDK's.

### 4. Attach a rule to your own message

If you have subclassed a message, declare the rule on it. Everything the attribute engine reads is
inherited, and `#[ValidatedBy]` on your subclass runs alongside whatever the parent declared.

### 5. Replace the whole thing

`ValidatorInterface` is one method. Implement it, pass it as `validator:`, and the SDK's rules are
gone entirely:

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

So it applies to every service method with a payload, and to nothing else — a method taking no
message has nothing to check. A service you write yourself and hand to the client through
`services:` inherits this if it extends `AbstractService`, and does not if it does not.

Testing against the rules
-------------------------

A validator is a plain object with one public method, so testing your own rules needs no HTTP at
all:

```php
$this->expectException(ValidationException::class);

(new AttributeValidator())->validate($badMessage);
```

And to assert on the report rather than just the type:

```php
try {
    (new AttributeValidator())->validate($badMessage);
    self::fail('Expected a rejection.');
} catch (ValidationException $e) {
    self::assertArrayHasKey('sender.postcode', $e->getValidationErrors());
}
```

See [Testing](testing.md) for the rest.
