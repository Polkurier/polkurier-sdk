Validation
==========

Payloads are checked before they are sent, so a bad payload fails locally instead of costing a
round trip.

What is checked
---------------

The rules follow what the API documents as required, so a payload the API would accept passes.
Validation stricter than the API would block valid calls, and the failure would look like the SDK
doing its job. On top of that, a few
checks catch payloads that are well-formed but can't do what you meant: a date range that runs
backwards and so matches nothing, a page size above the documented maximum, or a malformed order
number in a batch.

A disagreement with the API is a bug in the SDK. `validatePayloads: false` turns off every check,
and [changing the rules](#changing-the-rules) shows how to add rules or replace the validator.

What is not checked
-------------------

Nothing that depends on the carrier or the account: which services a carrier sells, whether it
needs a collection date, an account's PIN locks, the required PIN length, or the minimum prepaid
top-up. All of these can be read from `available_carriers`, `get_account_info` and
`get_system_info`. They can change between SDK releases, and the API checks them itself. Your own
code can check them before sending:

```php
$carriers = $client->courier()->getAvailableCarriers(new AvailableCarriersMessage(additionalData: true));
$limits   = $client->system()->getSystemInfo();
$account  = $client->account()->getAccountInfo();
```

Reading a rejection
-------------------

A rejected payload throws [`ValidationException`](../src/Exception/ValidationException.php) and **nothing is sent**. That's the key
difference: a [`ValidationException`](../src/Exception/ValidationException.php) means no request was made and nothing changed. A
[`WebserviceException`](../src/Exception/WebserviceException.php) with validation errors means the request was made and the API refused it.

```php
use Polkurier\Webservice\Exception\ValidationException;

try {
    $client->orders()->createOrder($message);
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

**Field names are wire names.** You get `sender.postcode`, not `sender.getPostcode()`. They match
the API's own validation errors and the request that would have been sent, so the same code can
display both.

**Paths are full paths.** Nested objects get a prefix and list entries get an index, so you can
find `packs[1].weight` without counting through the payload.

**Everything is reported at once.** Every rule on every field runs and all findings are collected,
so you can fix the payload in one pass.

```php
$e->getValidationErrors();            // array<string, string[]>: every field
$e->getFieldErrors('sender.postcode'); // string[]: one field, empty if it passed
$e->getViolations();                  // Violation[]: the same failures, with a code each
```

Messages are written to follow a field name ("must not be empty", "must be greater than zero"),
so `"$field $message"` reads as a sentence.

**The wording is not contract; the code is.** A message is phrased to follow the field name and
spells values out inside itself, so it cannot be matched on. Each
[`Violation`](../src/Validator/Violation.php) carries a
[`ValidationErrorCodeEnum`](../src/Enum/Validator/ValidationErrorCodeEnum.php) that does not
change, and the values the sentence names, so an integration can word its own report:

```php
foreach ($e->getViolations() as $violation) {
    $label = $this->translator->trans('polkurier.' . $violation->code->value, $violation->parameters);

    $this->addFieldError($violation->path, $label ?? $violation->message);
}
```

There is one code per condition, not per rule: a rule that rejects a value for several different
reasons reports a different code for each, so
[`Iban`](../src/Validator/Rule/Iban.php) distinguishes a wrong country from a wrong length from
failed check digits. A rule written for one message and described by no general code reports
[`ValidationErrorCodeEnum::Other`](../src/Enum/Validator/ValidationErrorCodeEnum.php), where
the English is all there is; fall back to `$violation->message` for it.

How the rules are attached
--------------------------

Each rule is an attribute on the thing it describes. No separate list or configuration file maps
messages to their rules, so none can get out of sync.

```php
#[Validate\InOrder(earlier: 'dateFrom', later: 'dateTo')]
#[Validate\InOrder(earlier: 'codMin', later: 'codMax')]
#[Validate\PageSizeAtMost(self::MAX_PAGE_SIZE)]
final readonly class GetOrdersMessage extends PagedMessage
{
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_COD_MIN)]
        public ?float $codMin = null,
        // …
    ) {}
}
```

A property rule sits next to the field: [`Address::$phone`](../src/Entity/Address.php) carries `#[Validate\NotBlank]` and
`#[Validate\PhoneNumber]`.

[`AttributeValidator`](../src/Validator/AttributeValidator.php) reads five kinds of declaration:

| Declaration | Where | What it does |
|-------------|-------|--------------|
| A [`ValueValidatorInterface`](../src/Validator/Rule/ValueValidatorInterface.php) | property | Checks that one value's format |
| [`#[Each(RuleClass::class)]`](../src/Validator/Rule/Each.php) | property | Apply one rule to every entry of a list of plain values |
| A [`MessageRuleInterface`](../src/Validator/Rule/MessageRuleInterface.php) | class | A rule about how two fields relate |
| [`#[IgnoredWhen(...)]`](../src/Validator/Rule/IgnoredWhen.php) | class | Skip every rule, because the block does not apply |
| [`#[ValidatedBy(Validator::class)]`](../src/Validator/Rule/ValidatedBy.php) | class | Run a validator written as a class |

The class-level kinds are inherited, so a rule declared on a base message applies to every
message that extends it.

A class rule can also name a field of a record the message inlines with
[`#[InlineFields]`](../src/Serializer/Attribute/InlineFields.php), because those fields are sent at the message's own level:
[`SavePhoneNumberMessage`](../src/Dto/Request/Account/SavePhoneNumberMessage.php) requires `twoFactorCode` when the [`PhoneNumber`](../src/Entity/PhoneNumber.php) it carries is
the account's default. An inlined record is checked at the enclosing level too, so its own fields
are reported under their plain wire names rather than under a prefix.

### Cross-field rules

A property attribute that names a sibling, like a hypothetical `#[Postcode(countryFrom: 'country')]`, links two
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
| [`NotBlank`](../src/Validator/Rule/NotBlank.php) | Present, and not just whitespace |
| [`NotEmptyList`](../src/Validator/Rule/NotEmptyList.php) | At least one non-blank entry |
| [`Positive`](../src/Validator/Rule/Positive.php) | Above zero: dimensions, weights, quantities |
| [`NonNegative`](../src/Validator/Rule/NonNegative.php) | Zero or above. Zero is meaningful for insurance and cash on delivery |
| [`IntegerRange(min, max)`](../src/Validator/Rule/IntegerRange.php) | A whole number within bounds, mostly for paging |
| [`Email`](../src/Validator/Rule/Email.php) | Permissive. Only catches what clearly isn't an address |
| [`PhoneNumber`](../src/Validator/Rule/PhoneNumber.php) | Length only, after stripping non-digits |
| [`Postcode`](../src/Validator/Rule/Postcode.php) / [`Postcode(country: 'DE')`](../src/Validator/Rule/Postcode.php) | The NN-NNN form. Other countries' formats are skipped |
| [`OrderNumber`](../src/Validator/Rule/OrderNumber.php) | The documented order-number format |
| [`Iban`](../src/Validator/Rule/Iban.php) | ISO 13616 check digits, on a full IBAN or a bare national number |
| [`OfType('string')`](../src/Validator/Rule/OfType.php) / [`OfType(PackItem::class)`](../src/Validator/Rule/OfType.php) | A value of that type, mostly for list entries through `Each` |

Only [`NotBlank`](../src/Validator/Rule/NotBlank.php) and [`NotEmptyList`](../src/Validator/Rule/NotEmptyList.php) reject a missing value.
Format rules pass on null, so the same postcode rule can be optional in one message and required
in another. [`Email`](../src/Validator/Rule/Email.php) and [`PhoneNumber`](../src/Validator/Rule/PhoneNumber.php) are loose. They only reject values that are
clearly wrong, because a stricter check would refuse recipients the carrier can deliver to.

[`Iban`](../src/Validator/Rule/Iban.php) is stricter. It fully verifies the check digits, because the field decides where collected
money is paid out and a typo would send it to the wrong account. For a country that isn't in its
registry only the check digits are verified, so an account the bank accepts isn't refused just
because the registry is behind. With `foreign: false` it accepts only accounts of its `country`; the
cash-on-delivery account uses that, because the API takes only Polish accounts there.

### Nested values

Nothing declares that a nested value is checked. Every field the SDK sends is descended into, so an
object is checked in place, every object in a list is checked, and the reported field includes the
path. A field marked [`#[ResponseOnly]`](../src/Serializer/Attribute/ResponseOnly.php) is not: it holds what was read rather than what is
about to be written, so a fault in it could not be fixed by changing the payload. Enums and dates
are values rather than records and are left to the field's own rules.

### Structural rules (on a property)

[`#[Each(OrderNumber::class)]`](../src/Validator/Rule/Each.php) applies one rule to every entry of a list of *plain values* and
reports errors against the index:

```php
#[Validate\NotEmptyList]
#[Validate\Each(Validate\OrderNumber::class)]
private array $orderNo;
```

The rule is given by class name. Any further arguments go to its constructor, which is how a list
declares the type of its entries: `#[Validate\Each(Validate\OfType::class, PackItem::class)]`. For a
rule that depends on a sibling field, there are message validators.

### Class rules

[`#[RequiredWhen(field: 'twoFactorCode', property: 'default', equals: true)]`](../src/Validator/Rule/RequiredWhen.php): a field becomes
required based on the value of another.

[`#[RequiredWith(field: 'externalApplicationId', with: 'externalOrderId')]`](../src/Validator/Rule/RequiredWith.php): a field becomes
required once another one is set, for 2 fields that only work as a pair. Declared once in each
direction when each needs the other, as on an order's external ids.

[`#[InOrder(earlier: 'dateFrom', later: 'dateTo')]`](../src/Validator/Rule/InOrder.php): a range that must run forwards. Works for
dates and numbers. If either end is missing there's no range to check, so it passes.

[`#[IgnoredWhen(property: 'amount', equals: 0.0)]`](../src/Validator/Rule/IgnoredWhen.php): the block doesn't apply, so none of its fields
are checked. The typical case is cash on delivery with nothing to collect. An amount of zero means
no collection, and the other fields in the block would otherwise fail their usual rules on a valid
payload.

[`#[PageSizeAtMost(self::MAX_PAGE_SIZE)]`](../src/Validator/Rule/PageSizeAtMost.php): the largest page size a paged request may ask for, on
the endpoints that have a limit.

[`#[ValidatedBy(SomeValidator::class)]`](../src/Validator/Rule/ValidatedBy.php): see below.

Validators written as classes
-----------------------------

Some rules need more logic than an attribute can hold, and a message names the class that checks
them with `#[ValidatedBy]`. An order address is the typical case. A sender must have an email
address and a recipient doesn't have to. A recipient collecting from a pickup point needs no street
address at all. An order's cover address is a sender address the carrier prints, so it is checked
as one: the API charges for that block as soon as any value in it is non-empty, and a half-filled
one would be printed. Which fields are required depends on the *role* of the address in the order,
which the address itself doesn't know. Registering a payment and checking a coupon against an order have
rules of this kind too.

The class names its validators, and a validator doesn't name its classes, so the link is declared
in one place only. [`OrderValidator`](../src/Validator/Message/OrderValidator.php) is declared on [`Order`](../src/Entity/Order.php), so it runs wherever an order is
sent: [`OrdersServiceInterface::orderValuation()`](../src/Api/Service/OrdersServiceInterface.php) as well as
[`OrdersServiceInterface::createOrder()`](../src/Api/Service/OrdersServiceInterface.php). To write one, see
[Add rules of your own](#2-add-rules-of-your-own).

Changing the rules
------------------

Three things here are part of the public contract and a release keeps them: `validatePayloads`,
[`ValidatorInterface`](../src/Api/Validation/ValidatorInterface.php) with its one method, and [`ValidatorRegistry`](../src/Validator/ValidatorRegistry.php) with
[`DefaultValidatorRegistryFactory`](../src/Validator/DefaultValidatorRegistryFactory.php). The machinery the SDK writes its own rules with —
[`AbstractMessageValidator`](../src/Validator/AbstractMessageValidator.php), [`MessageFieldReader`](../src/Validator/MessageFieldReader.php), [`ViolationPool`](../src/Validator/ViolationPool.php) and the
`Validate\*` attributes — is not, and a release may change any of it, see
[Architecture: what a release can change](architecture.md#what-a-release-can-change). Extending it
is supported in the sense that it works and is documented here; it ties your code to the SDK's
minor version.

### 1. Turn everything off

```php
new Config(/* … */, validatePayloads: false);
```

The services then skip validation entirely.

### 2. Add rules of your own

The registry runs every validator that applies and collects all findings. You can register
against a class or an interface. A validator registered for a base message covers every message
that extends it:

```php
use Polkurier\Webservice\Entity\Order;
use Polkurier\Webservice\Validator\DefaultValidatorRegistryFactory;

$validator = DefaultValidatorRegistryFactory::create()
    ->register(Order::class, new MyOrderPolicy());
```

`MyOrderPolicy` extends [`AbstractMessageValidator`](../src/Validator/AbstractMessageValidator.php), the base class above, so it collects
violations instead of throwing and its findings are reported along with the SDK's. A validator
that implements [`ValidatorInterface`](../src/Api/Validation/ValidatorInterface.php) directly stays on the contract, and throws its own
[`ValidationException`](../src/Exception/ValidationException.php) on the first problem it finds:

```php
use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\AbstractMessageValidator;
use Polkurier\Webservice\Validator\MessageFieldReader;
use Polkurier\Webservice\Validator\ViolationPool;

/** @extends AbstractMessageValidator<Order> */
final class MyOrderPolicy extends AbstractMessageValidator
{
    protected function collect(MessageInterface $data, ViolationPool $violations): void
    {
        $fields = new MessageFieldReader($data);

        if ($data->getDescription() === null) {
            $violations->add($fields->readField('description'), 'is required by our own policy');
        }
    }
}
```

[`ViolationPool`](../src/Validator/ViolationPool.php) has [`ViolationPool::add()`](../src/Validator/ViolationPool.php), [`ViolationPool::check()`](../src/Validator/ViolationPool.php) (run value validators against a value), [`ViolationPool::checkWhen()`](../src/Validator/ViolationPool.php) (run
them only if a condition holds) and [`ViolationPool::mergeUnder()`](../src/Validator/ViolationPool.php) (add a nested report under a prefix).
[`MessageFieldReader::readField()`](../src/Validator/MessageFieldReader.php) gives the wire name, so field names match the SDK's.

### 3. Attach a rule to your own message

A subclassed message can declare rules of its own. The attribute engine reads
inherited declarations, and `#[ValidatedBy]` on your subclass runs in addition to anything the
parent declares.

### 4. Replace the validator

[`ValidatorInterface`](../src/Api/Validation/ValidatorInterface.php) has one method. An implementation passed as `validator:` replaces all of the
SDK's rules:

```php
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

In [`AbstractService`](../src/Service/AbstractService.php), before the request is built, for every service method that takes a payload.
Methods without a message have nothing to check. A service of your own passed to the client
through `services:` gets this behaviour if it extends [`AbstractService`](../src/Service/AbstractService.php).

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
