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
use Polkurier\Webservice\Entity\Order\Cod;
use Polkurier\Webservice\Enum\Order\CodReturnTypeEnum;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\ValidationException;
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
            'a telephone number' => [new PhoneNumber(), '500600700'],
            'a telephone number with a country code' => [new PhoneNumber(), '+48 500 600 700'],
            'a positive integer' => [new Positive(), 1],
            'a positive float' => [new Positive(), 0.5],
            'zero, where zero is allowed' => [new NonNegative(), 0],
            'a value inside the range' => [new IntegerRange(1, 100), 100],
            'a non-empty string' => [new NotBlank(), 'x'],
            'a list with an entry' => [new NotEmptyList(), ['x']],
            // Presence is {@see NotBlank}'s business, so a format rule passes on an absent value.
            // Without that, every optional field carrying one would become mandatory.
            'an empty postcode, which NotBlank judges' => [new Postcode(), ''],
            'an empty account number, likewise' => [new Iban(), ''],
            'an empty telephone number, likewise' => [new PhoneNumber(), ''],
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
     * @return array<string, array{ValueValidatorInterface, mixed}>
     */
    public static function rejectedValues(): array
    {
        return [
            'a postcode with no separator' => [new Postcode(), '61001'],
            'a postcode of the wrong length' => [new Postcode(), '611-001'],
            'a postcode that is words' => [new Postcode(), 'Poznań'],
            'an order number with no separator' => [new OrderNumber(), '12345678'],
            'an order number that is words' => [new OrderNumber(), 'nope'],
            'an order number inside other text' => [new OrderNumber(), 'abc1-2xyz'],
            'an email address without a domain' => [new Email(), 'jan@'],
            'an email address with a space' => [new Email(), 'jan kowalski@example.com'],
            'a number where a string is expected' => [new OfType('string'), 7],
            'a string where an enum case is expected' => [new OfType(PackTypeEnum::class), 'ST'],
            'an account number that is too short' => [new Iban(), '5810203958'],
            'an account number with two digits transposed' => [new Iban(), '58102039580000920202228690'],
            'an account number with one digit changed' => [new Iban(), '58102039580000920202228608'],
            'an IBAN whose check digits do not hold' => [new Iban(), 'DE89370400440532013001'],
            'a foreign IBAN under foreign: false' => [new Iban(foreign: false), 'DE89370400440532013000'],
            'a telephone number that is too short' => [new PhoneNumber(), '12345'],
            'zero, where it must be positive' => [new Positive(), 0],
            'a negative number' => [new Positive(), -1],
            'a negative number where zero is the floor' => [new NonNegative(), -0.01],
            'a value above the range' => [new IntegerRange(1, 100), 101],
            'a value below the range' => [new IntegerRange(1, 100), 0],
            'an empty string' => [new NotBlank(), ''],
            'whitespace only' => [new NotBlank(), "  \t "],
            'null' => [new NotBlank(), null],
            'an empty list' => [new NotEmptyList(), []],
            'a list of blanks' => [new NotEmptyList(), ['', null]],
        ];
    }

    /**
     * @param ValueValidatorInterface $validator
     * @param mixed $value
     *
     * @return void
     */
    #[DataProvider('rejectedValues')]
    public function testUnacceptableValueIsReported(ValueValidatorInterface $validator, mixed $value): void
    {
        $problems = $validator->validate($value);

        self::assertNotSame([], $problems);
        foreach ($problems as $problem) {
            self::assertIsString($problem);
            self::assertNotSame('', \trim($problem));
        }
    }

    /**
     * {@see Each} names its rule by class, so a class that isn't a value rule is refused up front.
     */
    public function testEachRefusesAClassThatIsNotAValueRule(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Each(\stdClass::class); // @phpstan-ignore argument.type (the wrong class is the point)
    }

    /**
     * The messages are written to follow the field name, so that a report reads as a sentence
     * whichever field it is attached to.
     *
     * @return void
     */
    public function testMessagesReadAsAContinuationOfTheFieldName(): void
    {
        self::assertSame(['must not be empty'], (new NotBlank())->validate(''));
        self::assertSame(['must be greater than zero'], (new Positive())->validate(0));
        self::assertSame(['must be 28 characters long for PL'], (new Iban())->validate('123'));
        self::assertSame(['must be between 1 and 100'], (new IntegerRange(1, 100))->validate(500));
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
     * @param list<string> $failing
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
