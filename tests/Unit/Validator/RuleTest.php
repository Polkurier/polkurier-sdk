<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Validator;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Dto\Request\Courier\GetPointMessage;
use Polkurier\Webservice\Dto\Request\Geo\FindCityMessage;
use Polkurier\Webservice\Dto\Request\Order\GetOrdersMessage;
use Polkurier\Webservice\Dto\Request\Order\MultiPickupAvailabilityRequestMessage;
use Polkurier\Webservice\Dto\Request\Order\MultiPickupRequestMessage;
use Polkurier\Webservice\Entity\Order\Address;
use Polkurier\Webservice\Entity\Order\Cod;
use Polkurier\Webservice\Enum\Order\CodReturnTypeEnum;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Enum\Validator\ValidationErrorCodeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Test\Support\Arguments;
use Polkurier\Webservice\Validator\AttributeValidator;
use Polkurier\Webservice\Validator\Rule\Each;
use Polkurier\Webservice\Validator\Rule\Email;
use Polkurier\Webservice\Validator\Rule\Iban;
use Polkurier\Webservice\Validator\Rule\IntegerRange;
use Polkurier\Webservice\Validator\Rule\NonNegative;
use Polkurier\Webservice\Validator\Rule\NotBlank;
use Polkurier\Webservice\Validator\Rule\NotEmptyList;
use Polkurier\Webservice\Validator\Rule\OfType;
use Polkurier\Webservice\Validator\Rule\OrderNumber;
use Polkurier\Webservice\Validator\Rule\PhoneNumber;
use Polkurier\Webservice\Validator\Rule\Positive;
use Polkurier\Webservice\Validator\Rule\Postcode;
use Polkurier\Webservice\Validator\Rule\ValueValidatorInterface;
use Polkurier\Webservice\Validator\RuleFailure;

/**
 * The individual field rules, exercised on their own.
 *
 * These are the classes that appear as attributes on the messages. They are plain objects as well,
 * so they can be tested — and reused — without a message around them.
 *
 * Each one is reused across several messages, so a mistake in one of them is a mistake everywhere
 * it is applied — the postcode rule alone covers both ends of a shipment, the payer address and
 * the city lookup. Testing them here rather than only through the messages that compose them keeps
 * the blast radius visible.
 *
 * As with the message rules, the accepting cases matter more than the rejecting ones: a value
 * rejected here is a call the SDK refuses to make.
 *
 * A format rule accepts an absent value. Whether a field is required is decided by the message that
 * declares it, through {@see NotBlank}, so that the same format can be optional in one place and
 * mandatory in another.
 */
#[CoversNothing]
final class RuleTest extends TestCase
{
    /**
     * @return array<string, array{ValueValidatorInterface, mixed}>
     */
    public static function acceptedValues(): array
    {
        return [
            'a Polish postcode' => [new Postcode(), '61-001'],
            'a German postcode, which the rule does not judge' => [new Postcode('DE'), '10115'],
            'a British postcode, likewise' => [new Postcode('GB'), 'SW1A 1AA'],
            'an order number' => [new OrderNumber(), '12345-678'],
            'an email address' => [new Email(), 'jan@example.com'],
            'an email address left out' => [new Email(), ''],
            'a string where a string is expected' => [new OfType('string'), 'INPOST'],
            'an enum case of the expected enum' => [new OfType(PackTypeEnum::class), PackTypeEnum::Standard],
            'an order number with a longer prefix' => [new OrderNumber(), '999999-1'],
            'a Polish account number' => [new Iban(), '34102010260000000000000001'],
            'the same, written with spaces' => [new Iban(), '58 1020 3958 0000 9202 0222 8609'],
            'the same, as a full IBAN' => [new Iban(), 'PL34102010260000000000000001'],
            'a German IBAN' => [new Iban(), 'DE89370400440532013000'],
            'a British IBAN, which has letters in it' => [new Iban(), 'GB82WEST12345698765432'],
            'a Polish IBAN under foreign: false' => [new Iban(foreign: false), 'PL34102010260000000000000001'],
            'a phone number' => [new PhoneNumber(), '500600700'],
            'a phone number with a country code' => [new PhoneNumber(), '+48 500 600 700'],
            // A Danish number is 8 digits and an Icelandic one 7, so a Polish length would reject
            // a recipient the carrier delivers to.
            'a foreign phone number, shorter than a Polish one' => [new PhoneNumber(), '12345678'],
            'a positive integer' => [new Positive(), 1],
            'a positive float' => [new Positive(), 0.5],
            'zero, where zero is allowed' => [new NonNegative(), 0],
            'a value inside the range' => [new IntegerRange(1, 100), 100],
            'a non-empty string' => [new NotBlank(), 'x'],
            'a list with an entry' => [new NotEmptyList(), ['x']],
            'a list left out' => [new NotEmptyList(), null],
            'a typed value left out' => [new OfType('string'), null],
            // Presence is {@see NotBlank}'s business, so a format rule passes on an absent value.
            // Without that, every optional field carrying one would become mandatory.
            'an empty postcode, which NotBlank judges' => [new Postcode(), ''],
            'an empty account number, likewise' => [new Iban(), ''],
            'an account number left out, likewise' => [new Iban(), null],
            'an empty phone number, likewise' => [new PhoneNumber(), ''],
            'a phone number left out, likewise' => [new PhoneNumber(), null],
            'an empty order number, likewise' => [new OrderNumber(), ''],
            'a null page size, likewise' => [new IntegerRange(1, 100), null],
        ];
    }

    /**
     * @param ValueValidatorInterface $validator
     * @param mixed $value
     *
     * @return void
     */
    #[DataProvider('acceptedValues')]
    public function testAcceptableValueIsNotReported(ValueValidatorInterface $validator, mixed $value): void
    {
        self::assertSame([], $validator->validate($value));
    }

    /**
     * @return array<string, array{ValueValidatorInterface, mixed, ValidationErrorCodeEnum}>
     */
    public static function rejectedValues(): array
    {
        return [
            'a postcode with no separator' => [new Postcode(), '61001', ValidationErrorCodeEnum::NotAPostcode],
            'a postcode of the wrong length' => [new Postcode(), '611-001', ValidationErrorCodeEnum::NotAPostcode],
            'a postcode that is words' => [new Postcode(), 'Poznań', ValidationErrorCodeEnum::NotAPostcode],
            'an order number with no separator' => [new OrderNumber(), '12345678', ValidationErrorCodeEnum::NotAnOrderNumber],
            'an order number that is words' => [new OrderNumber(), 'nope', ValidationErrorCodeEnum::NotAnOrderNumber],
            'an order number inside other text' => [new OrderNumber(), 'abc1-2xyz', ValidationErrorCodeEnum::NotAnOrderNumber],
            'an email address without a domain' => [new Email(), 'jan@', ValidationErrorCodeEnum::NotAnEmail],
            'an email address with a space' => [new Email(), 'jan kowalski@example.com', ValidationErrorCodeEnum::NotAnEmail],
            'a number where a string is expected' => [new OfType('string'), 7, ValidationErrorCodeEnum::WrongType],
            'a string where an enum case is expected' => [new OfType(PackTypeEnum::class), 'ST', ValidationErrorCodeEnum::WrongType],
            'an account number that is too short' => [new Iban(), '5810203958', ValidationErrorCodeEnum::BankAccountWrongLength],
            'an account number with two digits transposed' => [new Iban(), '58102039580000920202228690', ValidationErrorCodeEnum::BankAccountBadChecksum],
            'an account number with one digit changed' => [new Iban(), '58102039580000920202228608', ValidationErrorCodeEnum::BankAccountBadChecksum],
            'an IBAN whose check digits do not hold' => [new Iban(), 'DE89370400440532013001', ValidationErrorCodeEnum::BankAccountBadChecksum],
            'a foreign IBAN under foreign: false' => [new Iban(foreign: false), 'DE89370400440532013000', ValidationErrorCodeEnum::BankAccountWrongCountry],
            'a phone number that is too short' => [new PhoneNumber(), '12', ValidationErrorCodeEnum::PhoneNumberTooShort],
            'zero, where it must be positive' => [new Positive(), 0, ValidationErrorCodeEnum::OutOfRange],
            'a negative number' => [new Positive(), -1, ValidationErrorCodeEnum::OutOfRange],
            'a negative number where zero is the floor' => [new NonNegative(), -0.01, ValidationErrorCodeEnum::OutOfRange],
            'a value above the range' => [new IntegerRange(1, 100), 101, ValidationErrorCodeEnum::OutOfRange],
            'a value below the range' => [new IntegerRange(1, 100), 0, ValidationErrorCodeEnum::OutOfRange],
            'an empty string' => [new NotBlank(), '', ValidationErrorCodeEnum::Blank],
            'whitespace only' => [new NotBlank(), "  \t ", ValidationErrorCodeEnum::Blank],
            'null' => [new NotBlank(), null, ValidationErrorCodeEnum::Blank],
            'an empty list' => [new NotEmptyList(), [], ValidationErrorCodeEnum::EmptyList],
            'a list of blanks' => [new NotEmptyList(), ['', null], ValidationErrorCodeEnum::EmptyList],
            'letters where a phone number belongs' => [
                new PhoneNumber(), 'abcdefghi', ValidationErrorCodeEnum::NotAPhoneNumber,
            ],
            'a bank account with characters it cannot hold' => [
                new Iban(), '58102039580000920202-2286!0', ValidationErrorCodeEnum::BankAccountBadCharacters,
            ],
            'a bank account written only with separators' => [
                new Iban(), ' - - ', ValidationErrorCodeEnum::NotABankAccount,
            ],
            'a decimal where a whole number belongs' => [
                new IntegerRange(1, 100), 1.5, ValidationErrorCodeEnum::NotAWholeNumber,
            ],
            'a string where a positive number belongs' => [
                new Positive(), 'lots', ValidationErrorCodeEnum::NotANumber,
            ],
            'a list where one value belongs' => [
                new NotEmptyList(), 'nope', ValidationErrorCodeEnum::NotAList,
            ],
        ];
    }

    /**
     * The code is the part a caller branches on, so each rejected value names the fault it reports
     * rather than only proving that something was reported.
     *
     * @param ValueValidatorInterface $validator
     * @param mixed $value
     * @param ValidationErrorCodeEnum $code
     *
     * @return void
     */
    #[DataProvider('rejectedValues')]
    public function testUnacceptableValueIsReported(
        ValueValidatorInterface $validator,
        mixed $value,
        ValidationErrorCodeEnum $code,
    ): void {
        $problems = $validator->validate($value);

        self::assertNotSame([], $problems);
        self::assertSame($code, $problems[0]->code);
        foreach ($problems as $problem) {
            self::assertNotSame('', \trim($problem->message));
            // A rule that cannot say what is wrong beyond its own sentence belongs to a message,
            // not to a value, so a value rule never reports the fallback.
            self::assertNotSame(ValidationErrorCodeEnum::Other, $problem->code);
        }
    }

    /**
     * @return array<string, array{ValueValidatorInterface, mixed, ValidationErrorCodeEnum, array<string, scalar>}>
     */
    public static function faults(): array
    {
        return [
            // A value with no digits in it, or one written out of the separators an account number
            // uses, is something the customer typed, not an absent field: the format rule names
            // the fault instead of borrowing the one {@see NotBlank} reports.
            'a phone number that is letters' => [
                new PhoneNumber(),
                'abc',
                ValidationErrorCodeEnum::NotAPhoneNumber,
                [],
            ],
            'a phone number that is plus signs' => [
                new PhoneNumber(),
                '+++',
                ValidationErrorCodeEnum::NotAPhoneNumber,
                [],
            ],
            'a phone number that is hyphens' => [
                new PhoneNumber(),
                '---',
                ValidationErrorCodeEnum::NotAPhoneNumber,
                [],
            ],
            'a phone number that is not a string' => [
                new PhoneNumber(),
                Arguments::widen(500600700),
                ValidationErrorCodeEnum::NotAPhoneNumber,
                [],
            ],
            'a phone number with too few digits' => [
                new PhoneNumber(),
                '12',
                ValidationErrorCodeEnum::PhoneNumberTooShort,
                ['minimum' => 4],
            ],
            'an account number that is spaces' => [
                new Iban(),
                '   ',
                ValidationErrorCodeEnum::NotABankAccount,
                [],
            ],
            'an account number that is hyphens' => [
                new Iban(),
                '---',
                ValidationErrorCodeEnum::NotABankAccount,
                [],
            ],
            'an account number that is the separators a written one is grouped with' => [
                new Iban(),
                ' - - ',
                ValidationErrorCodeEnum::NotABankAccount,
                [],
            ],
            'an account number that is not a string' => [
                new Iban(),
                Arguments::widen(1020),
                ValidationErrorCodeEnum::NotABankAccount,
                [],
            ],
            'an account number too short for its country' => [
                new Iban(),
                '5810203958',
                ValidationErrorCodeEnum::BankAccountWrongLength,
                ['country' => 'PL', 'expected' => 28],
            ],
        ];
    }

    /**
     * A rejected value names which fault it is and carries the values its sentence spells out, so
     * an integration words its own report from those instead of matching on the English.
     *
     * @param ValueValidatorInterface $validator
     * @param mixed $value
     * @param ValidationErrorCodeEnum $code
     * @param array<string, scalar> $parameters
     *
     * @return void
     */
    #[DataProvider('faults')]
    public function testARejectedValueNamesItsFaultAndTheValuesItsMessageSpellsOut(
        ValueValidatorInterface $validator,
        mixed $value,
        ValidationErrorCodeEnum $code,
        array $parameters,
    ): void {
        $failures = $validator->validate($value);

        self::assertCount(1, $failures);
        self::assertSame($code, $failures[0]->code);
        self::assertSame($parameters, $failures[0]->parameters);
    }

    /**
     * {@see Each} names its rule by class, so a class that isn't a value rule is refused up front.
     */
    public function testEachRefusesAClassThatIsNotAValueRule(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Each(Arguments::widen(\stdClass::class));
    }

    /**
     * The messages are written to follow the field name, so that a report reads as a sentence
     * whichever field it is attached to.
     *
     * @return void
     */
    public function testMessagesReadAsAContinuationOfTheFieldName(): void
    {
        self::assertSame(['must not be empty'], self::messagesOf((new NotBlank())->validate('')));
        self::assertSame(['must be greater than zero'], self::messagesOf((new Positive())->validate(0)));
        self::assertSame(['must be 28 characters long for PL'], self::messagesOf((new Iban())->validate('123')));
        self::assertSame(['must be a phone number'], self::messagesOf((new PhoneNumber())->validate('abc')));
        self::assertSame(
            ['must be a bank account number'],
            self::messagesOf((new Iban())->validate(' - ')),
        );
        self::assertSame(
            ['must be between 1 and 100'],
            self::messagesOf((new IntegerRange(1, 100))->validate(500)),
        );
        self::assertSame(
            ['must be an account number from PL'],
            self::messagesOf((new Iban(foreign: false))->validate('DE89370400440532013000')),
        );
    }

    /**
     * The type name ends the sentence, and `int` and `Address` would take different articles, so
     * the wording takes none.
     *
     * @return void
     */
    public function testATypeFailureIsWordedForEveryTypeNameItCanCarry(): void
    {
        self::assertSame(['must be of type int'], self::messagesOf((new OfType('int'))->validate(1.5)));
        self::assertSame(
            ['must be of type Address'],
            self::messagesOf((new OfType(Address::class))->validate('Długa 12')),
        );
    }

    /**
     * @param RuleFailure[] $failures
     *
     * @return string[]
     */
    private static function messagesOf(array $failures): array
    {
        return \array_map(static fn(RuleFailure $failure): string => $failure->message, $failures);
    }

    /**
     * @return array<string, array{MessageInterface, list<string>}>
     */
    public static function classRuleCases(): array
    {
        $day = new \DateTimeImmutable('2026-09-01');

        return [
            'InOrder: equal ends pass' => [new GetOrdersMessage(dateFrom: $day, dateTo: $day), []],
            'InOrder: a reversed range fails' => [
                new GetOrdersMessage(dateFrom: $day, dateTo: $day->modify('-1 day')),
                ['date_from'],
            ],
            'RequiredWhen: a bank account is needed for a bank transfer' => [
                new Cod(amount: 100.0),
                ['codbankaccount'],
            ],
            'RequiredWhen: not needed for another return type' => [
                new Cod(amount: 100.0, returnType: CodReturnTypeEnum::cases()[1]),
                [],
            ],
            'RequiredWith: an external order id needs its application id' => [
                new GetOrdersMessage(externalOrderId: 'SHOP-1001'),
                ['external_application_id'],
            ],
            'RequiredWith: the application id works on its own' => [
                new GetOrdersMessage(externalApplicationId: 'app'),
                [],
            ],
            'RequiredWith: both together pass' => [
                new GetOrdersMessage(externalApplicationId: 'app', externalOrderId: 'SHOP-1001'),
                [],
            ],
            'RequiredWith: a page of points needs a page size' => [
                new GetPointMessage(couriers: ['INPOST_PACZKOMAT'], page: 2),
                ['limit'],
            ],
            'RequiredWith: a page size of points works on its own' => [
                new GetPointMessage(couriers: ['INPOST_PACZKOMAT'], limit: 100),
                [],
            ],
            'RequiredWith: a page of points with its size passes' => [
                new GetPointMessage(couriers: ['INPOST_PACZKOMAT'], limit: 100, page: 2),
                [],
            ],
            'IgnoredWhen: a zero amount skips the block' => [
                new Cod(amount: 0.0, bankAccount: 'not-an-iban'),
                [],
            ],
            'Each: every entry is checked' => [
                new MultiPickupRequestMessage(
                    pickupDate: $day,
                    pickupTimeFrom: $day,
                    pickupTimeTo: $day,
                    orders: ['1234-5', ''],
                ),
                ['orders[1]'],
            ],
            'NotBlank and NotEmptyList: an availability check names a carrier and a parcel' => [
                new MultiPickupAvailabilityRequestMessage(courier: '', packs: []),
                ['courier', 'packs'],
            ],
            'Each: a collective pickup takes order numbers, not waybills' => [
                new MultiPickupRequestMessage(
                    pickupDate: $day,
                    pickupTimeFrom: $day,
                    pickupTimeTo: $day,
                    orders: ['711400-30', '3301791288655'],
                ),
                ['orders[1]'],
            ],
            // The message's own country field defaults to PL, so blanking it is an override of
            // that default, not a payload without a country.
            'PostcodeForCountry: falls back to Polish postcodes' => [
                new FindCityMessage(postcode: '12345', country: ''),
                ['postcode'],
            ],
            'PostcodeForCountry: another country is not parsed' => [
                new FindCityMessage(postcode: '12345', country: 'DE'),
                [],
            ],
        ];
    }

    /**
     * @param MessageInterface $message
     * @param list<string> $failing the wire paths the message's class rules should reject
     *
     * @return void
     */
    #[DataProvider('classRuleCases')]
    public function testClassRulesAndEach(MessageInterface $message, array $failing): void
    {
        try {
            (new AttributeValidator())->validate($message);
            self::assertSame([], $failing);
        } catch (ValidationException $e) {
            self::assertSame($failing, \array_keys($e->getValidationErrors()));
        }
    }
}
