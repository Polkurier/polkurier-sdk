<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Log;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Dto\Request\Account\SavePayerAddressMessage;
use Polkurier\Webservice\Dto\Request\BankAccount\SaveBankAccountMessage;
use Polkurier\Webservice\Dto\Request\Contact\SaveContactFormMessage;
use Polkurier\Webservice\Dto\Response\Account\AccountInfoMessage;
use Polkurier\Webservice\Dto\Response\Invoice\InvoiceMessage;
use Polkurier\Webservice\Dto\Response\MoneyBox\MoneyBoxConfigMessage;
use Polkurier\Webservice\Dto\Response\Ticket\TicketListMessage;
use Polkurier\Webservice\Dto\Response\Ticket\TicketMessage;
use Polkurier\Webservice\Dto\Response\Ticket\TicketTimelineItemMessage;
use Polkurier\Webservice\Dto\Response\TwoFactorToken;
use Polkurier\Webservice\Entity\Address;
use Polkurier\Webservice\Entity\BankAccount;
use Polkurier\Webservice\Entity\Order;
use Polkurier\Webservice\Entity\Order\Address as OrderAddress;
use Polkurier\Webservice\Entity\Order\Cod;
use Polkurier\Webservice\Entity\PhoneNumber;
use Polkurier\Webservice\Http\Request\RequestMessage;
use Polkurier\Webservice\Log\Attribute\PersonalData;
use Polkurier\Webservice\Log\PersonalDataRedactor;
use Polkurier\Webservice\Serializer\ClassMemberReader;
use Polkurier\Webservice\Serializer\WireNameReader;
use Polkurier\Webservice\Test\Support\SourceClasses;

#[CoversClass(PersonalDataRedactor::class)]
final class PersonalDataRedactorTest extends TestCase
{
    private const string NUMBER = '61109010140000071219812874';

    /**
     * The kinds of personal data a logged body hides are documented, so an integration can stop
     * hiding them itself. The set only grows, which is why this names each kind, with the class
     * that declares it, rather than the list.
     *
     * @see docs/configuration.md
     *
     * @return array<string, array{class-string, string}>
     */
    public static function kindsTheContractNames(): array
    {
        return [
            'a person' => [OrderAddress::class, OrderAddress::FIELD_PERSON],
            'a company' => [OrderAddress::class, OrderAddress::FIELD_COMPANY],
            'a street' => [OrderAddress::class, OrderAddress::FIELD_STREET],
            'a street without its numbers' => [OrderAddress::class, OrderAddress::FIELD_STREET_NAME],
            'a house number' => [OrderAddress::class, OrderAddress::FIELD_HOUSE_NUMBER],
            'a flat number' => [OrderAddress::class, OrderAddress::FIELD_FLAT_NUMBER],
            'a postcode' => [OrderAddress::class, OrderAddress::FIELD_POSTCODE],
            'a city' => [OrderAddress::class, OrderAddress::FIELD_CITY],
            'an email address' => [OrderAddress::class, OrderAddress::FIELD_EMAIL],
            'a phone number' => [OrderAddress::class, OrderAddress::FIELD_PHONE],
            'a payer name' => [SavePayerAddressMessage::class, SavePayerAddressMessage::FIELD_NAME],
            'a building number' => [
                SavePayerAddressMessage::class,
                SavePayerAddressMessage::FIELD_BUILDING_NUMBER,
            ],
            'a postcode under the payer spelling' => [
                SavePayerAddressMessage::class,
                SavePayerAddressMessage::FIELD_POST_CODE,
            ],
            'a tax number' => [SavePayerAddressMessage::class, SavePayerAddressMessage::FIELD_NIP],
            'a company registration number' => [
                SavePayerAddressMessage::class,
                SavePayerAddressMessage::FIELD_REGON,
            ],
            'a cash-on-delivery bank account' => [Cod::class, Cod::FIELD_BANK_ACCOUNT],
            'a money box bank account' => [
                MoneyBoxConfigMessage::class,
                MoneyBoxConfigMessage::FIELD_BANK_ACCOUNT_NUMBER,
            ],
            'an invoice payment account' => [
                AccountInfoMessage::class,
                AccountInfoMessage::FIELD_INVOICE_PAYMENT_ACCOUNT_NUMBER,
            ],
            'an account display name' => [AccountInfoMessage::class, AccountInfoMessage::FIELD_DISPLAY_NAME],
            'a ticket author' => [
                TicketTimelineItemMessage::class,
                TicketTimelineItemMessage::FIELD_AUTHOR_NAME,
            ],
            'the employee answering a ticket' => [TicketMessage::class, TicketMessage::FIELD_EMPLOYEE_NAME],
            'the number a two-factor code went to' => [TwoFactorToken::class, TwoFactorToken::FIELD_PHONE_NUMBER],
            'a registered bank account' => [BankAccount::class, BankAccount::FIELD_NUMBER],
            'a registered phone number' => [PhoneNumber::class, PhoneNumber::FIELD_NUMBER],
        ];
    }

    #[DataProvider('kindsTheContractNames')]
    public function testHidesEveryKindOfPersonalDataTheContractNames(string $class, string $field): void
    {
        self::assertSame([$field => '***'], self::redacted($class, [$field => 'Jan Kowalski']));
    }

    /**
     * Every member the source marks, under the name its class writes it. A mark the reader cannot
     * match to a wire name hides nothing, and nothing else would report it.
     *
     * @return array<string, array{class-string, string}>
     */
    public static function markedFields(): array
    {
        $cases = [];
        foreach (SourceClasses::instantiableUnder() as $name => $class) {
            foreach (ClassMemberReader::readMarkedWith($class, PersonalData::class) as $member) {
                $field = WireNameReader::read($member);
                $cases[$name . ' ' . $field] = [$class, $field];
            }
        }

        self::assertNotEmpty($cases);

        return $cases;
    }

    #[DataProvider('markedFields')]
    public function testHidesEveryFieldMarkedAsPersonalData(string $class, string $field): void
    {
        self::assertSame([$field => '***'], self::redacted($class, [$field => 'Jan Kowalski']));
    }

    /**
     * The same value under the same key, hidden or logged by the class around it alone: `number`
     * describes a person on a bank account and on a phone number, and names a record on an order,
     * a ticket and an invoice.
     *
     * @return array<string, array{class-string, string, string}>
     */
    public static function classesThatWriteANumber(): array
    {
        return [
            'a bank account' => [BankAccount::class, BankAccount::FIELD_NUMBER, '***'],
            'a phone number' => [PhoneNumber::class, PhoneNumber::FIELD_NUMBER, '***'],
            'an order' => [Order::class, Order::FIELD_NUMBER, self::NUMBER],
            'a ticket' => [TicketMessage::class, TicketMessage::FIELD_NUMBER, self::NUMBER],
            'an invoice' => [InvoiceMessage::class, InvoiceMessage::FIELD_NUMBER, self::NUMBER],
        ];
    }

    #[DataProvider('classesThatWriteANumber')]
    public function testHidesANumberOnlyOnTheClassesThatMarkIt(string $class, string $field, string $logged): void
    {
        self::assertSame([$field => $logged], self::redacted($class, [$field => self::NUMBER]));
    }

    /**
     * A record the payload inlines writes its fields at the enclosing level, so its marks govern
     * them there: the account number is the customer's, the label beside it is the customer's own
     * wording.
     *
     * @return void
     */
    public function testHidesTheMarkedFieldOfAnInlinedRecordAtTheEnclosingLevel(): void
    {
        $saved = [BankAccount::FIELD_NUMBER => self::NUMBER, BankAccount::FIELD_NAME => 'Firmowe'];

        self::assertSame(
            [BankAccount::FIELD_NUMBER => '***', BankAccount::FIELD_NAME => 'Firmowe'],
            self::redacted(SaveBankAccountMessage::class, $saved),
        );
    }

    /**
     * A collection's element class comes from the `@var Foo[]` docblock the serializer reads it
     * from, so every entry is walked as that class.
     *
     * @return void
     */
    public function testHidesTheMarkedFieldOfEveryEntryOfAList(): void
    {
        $tickets = [
            [TicketMessage::FIELD_NUMBER => 'R-1', TicketMessage::FIELD_EMPLOYEE_NAME => 'Anna N.'],
            [TicketMessage::FIELD_NUMBER => 'R-2', TicketMessage::FIELD_EMPLOYEE_NAME => 'Piotr W.'],
        ];

        self::assertSame(
            [TicketListMessage::FIELD_RESULT => [
                [TicketMessage::FIELD_NUMBER => 'R-1', TicketMessage::FIELD_EMPLOYEE_NAME => '***'],
                [TicketMessage::FIELD_NUMBER => 'R-2', TicketMessage::FIELD_EMPLOYEE_NAME => '***'],
            ]],
            self::redacted(TicketListMessage::class, [TicketListMessage::FIELD_RESULT => $tickets]),
        );
    }

    /**
     * An address book entry's id and the label the customer gave it name the record a log entry
     * belongs to without describing the person in it.
     *
     * @return void
     */
    public function testKeepsTheIdentifiersALogIsTracedBy(): void
    {
        $entry = [
            Address::FIELD_ID => '7',
            Address::FIELD_NAME => 'Magazyn',
            Address::FIELD_PERSON => 'Jan Kowalski',
        ];

        self::assertSame(
            [Address::FIELD_ID => '7', Address::FIELD_NAME => 'Magazyn', Address::FIELD_PERSON => '***'],
            self::redacted(Address::class, $entry),
        );
    }

    /**
     * A contact form's answers are sent under the names the form definition gives them, so no
     * property can carry a mark for any one of them. The mark sits on the field holding the map
     * and covers every key arriving beside the ones the message writes itself.
     *
     * @return void
     */
    public function testHidesEveryAnswerWhileKeepingTheFieldsTheMessageWritesItself(): void
    {
        $submitted = [
            SaveContactFormMessage::FIELD_CATEGORY => '12',
            SaveContactFormMessage::FIELD_FORM_ID => 'f-1',
            'nazwa_firmy_klienta' => 'Polkurier sp. z o.o.',
            'email' => 'jan@example.com',
            'tresc' => 'Gdzie jest moja paczka?',
        ];

        self::assertSame(
            [
                SaveContactFormMessage::FIELD_CATEGORY => '12',
                SaveContactFormMessage::FIELD_FORM_ID => 'f-1',
                'nazwa_firmy_klienta' => '***',
                'email' => '***',
                'tresc' => '***',
            ],
            self::redacted(SaveContactFormMessage::class, $submitted),
        );
    }

    /**
     * An order's cover address is sent with the flat number under `flat_number`, which
     * {@see \Polkurier\Webservice\Serializer\RequestQuirksNormalizer} renames from the `flatnumber`
     * that {@see OrderAddress::$flatNumber} declares and marks, so the lookup misses the key the
     * body carries and the flat number is logged.
     *
     * Redacting `[Order::FIELD_COVER_ADDRESS => [person, flat_number]]` as an {@see Order} would
     * hide both; today it hides the person alone.
     *
     * @return void
     */
    public function testHidesTheFlatNumberOfAnOrdersCoverAddress(): void
    {
        self::markTestSkipped(
            'Open: whether the mark follows the rename or `cover_address` stops renaming the key.',
        );
    }

    /**
     * The fields as a logged body carries them: under one key, walked beside the class that wrote
     * them. A class map names the keys of the level below the body's own, and which key that is
     * belongs to the caller, not to the redactor.
     *
     * @param class-string $class
     * @param array<string, mixed> $fields
     *
     * @return array<array-key, mixed>
     */
    /**
     * A contact form names its own fields, so a field name can be anything the form chooses,
     * including something that spells a number. `json_decode` reads the key of `{"17": …}` as the
     * integer 17, and a mark is keyed by the wire name as written, so a level whose keys a class
     * declares is read as an object whatever its keys spell.
     *
     * @return void
     */
    public function testAFieldWhoseNameSpellsANumberIsStillMasked(): void
    {
        self::assertSame(
            ['17' => PersonalDataRedactor::MASK, 'name' => PersonalDataRedactor::MASK],
            self::redacted(SaveContactFormMessage::class, ['17' => 'Jan Kowalski', 'name' => 'Jan Kowalski']),
        );
    }

    /**
     * The fields as a logged body carries them: under one key, walked beside the class that wrote
     * them. A class map names the keys of the level below the body's own, and which key that is
     * belongs to the caller, not to the redactor.
     *
     * @param class-string $class
     * @param array<array-key, mixed> $fields the keys as PHP holds them: a wire name that spells a
     *        number is an integer here, as it is after the body is decoded
     *
     * @return array<array-key, mixed>
     */
    private static function redacted(string $class, array $fields): array
    {
        $body = (string)\json_encode([RequestMessage::FIELD_DATA => $fields]);
        $redacted = \json_decode(
            (new PersonalDataRedactor())->redactJson($body, [RequestMessage::FIELD_DATA => $class]),
            true,
        );

        self::assertIsArray($redacted);
        self::assertIsArray($redacted[RequestMessage::FIELD_DATA]);

        return $redacted[RequestMessage::FIELD_DATA];
    }
}
