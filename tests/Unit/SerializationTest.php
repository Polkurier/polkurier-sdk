<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Enum\Account\RegulationsTypeEnum;
use Polkurier\Webservice\Enum\Address\AddressTypeEnum;
use Polkurier\Webservice\Enum\Courier\PointFunctionEnum;
use Polkurier\Webservice\Enum\Invoice\InvoiceTypeEnum;
use Polkurier\Webservice\Enum\Order\CodReturnTypeEnum;
use Polkurier\Webservice\Enum\Order\CodTypeEnum;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Enum\TwoFactorToken\TwoFactorNamespaceEnum;
use Polkurier\Webservice\Response\CourierPoint;
use Polkurier\Webservice\Request\Account\GetRegulationsMessage;
use Polkurier\Webservice\Request\Address\DeleteMessage as AddressDeleteMessage;
use Polkurier\Webservice\Request\Address\SaveMessage as AddressSaveMessage;
use Polkurier\Webservice\Request\BankAccount\SaveMessage as BankAccountSaveMessage;
use Polkurier\Webservice\Request\Courier\GetPointMessage;
use Polkurier\Webservice\Request\Invoice\GetInvoicesMessage;
use Polkurier\Webservice\Request\Order\CodMessage;
use Polkurier\Webservice\Request\Order\GetDocumentMessage;
use Polkurier\Webservice\Request\Order\GetOrdersMessage;
use Polkurier\Webservice\Request\Order\GetStatusMessage;
use Polkurier\Webservice\Request\Order\OrderIdMessage;
use Polkurier\Webservice\Request\Order\PackMessage;
use Polkurier\Webservice\Request\System\ValidateCouponMessage;
use Polkurier\Webservice\Request\Order\PickupMessage;
use Polkurier\Webservice\Request\PackTemplate\SaveMessage as PackTemplateSaveMessage;
use Polkurier\Webservice\Request\TwoFactorToken\RegisterMessage;
use Polkurier\Webservice\Request\Contact\SaveContactFormMessage;
use Polkurier\Webservice\Request\Account\SavePayerAddressMessage;
use Polkurier\Webservice\Enum\Account\PayerAddressTypeEnum;
use Polkurier\Webservice\Request\Ticket\CreateTicketMessageMessage;
use Polkurier\Webservice\Request\Ticket\CreateTicketMessage;
use Polkurier\Webservice\Serializer\SerializerProvider;

/**
 * The keys and values the SDK actually puts on the wire.
 *
 * The API matches field names exactly and silently ignores anything it does not recognise, so a
 * renamed key is not an error — it is a parameter that quietly never arrives. That is how `orderno`
 * went out as `order_no` and broke four methods at once without a single failing assertion
 * anywhere. Every message whose keys matter is therefore asserted against the literal JSON.
 */
#[CoversNothing]
final class SerializationTest extends TestCase
{
    /**
     * @param array<array-key, mixed> $value
     *
     * @return array<array-key, mixed>
     */
    private static function sortDeep(array $value): array
    {
        foreach ($value as $key => $item) {
            if (\is_array($item)) {
                $value[$key] = self::sortDeep($item);
            }
        }

        \ksort($value);

        return $value;
    }

    /**
     * A message and the exact JSON the API must receive.
     *
     * @return array<string, array{object, array<string, mixed>}>
     */
    public static function messagesAndTheirWireForm(): array
    {
        return [
            'a payer address the same as the payer, with no blank fields' => [
                new SavePayerAddressMessage(addressType: PayerAddressTypeEnum::Payer, sameAsPayer: true),
                ['address_type' => 'PAYER', 'same_as_payer' => true],
            ],
            'cash on delivery with no bank account given' => [
                new CodMessage(amount: 100.0),
                ['codamount' => 100.0, 'codtype' => 'S', 'return_cod' => 'BA'],
            ],
            'a ticket reply with no attachments' => [
                new CreateTicketMessageMessage(ticketId: 't1', content: 'Hi'),
                ['ticket_id' => 't1', 'content' => 'Hi'],
            ],
            'a ticket with no attachments' => [
                new CreateTicketMessage(topicId: '5', content: 'Hi'),
                ['topic_id' => '5', 'content' => 'Hi', 'name' => ''],
            ],
            'a contact form, its answers next to the fixed keys' => [
                new SaveContactFormMessage(
                    category: 'wspolpraca',
                    formId: 'f-1',
                    nonce: 'n-1',
                    fields: ['email' => 'jan@example.com', 'consent' => true],
                ),
                ['category' => 'wspolpraca', 'form_id' => 'f-1', 'nonce' => 'n-1', 'email' => 'jan@example.com', 'consent' => true],
            ],
            'a contact form answer never replaces a fixed key' => [
                new SaveContactFormMessage(category: 'wspolpraca', formId: 'f-1', nonce: 'n-1', fields: ['nonce' => 'forged']),
                ['category' => 'wspolpraca', 'form_id' => 'f-1', 'nonce' => 'n-1'],
            ],
            'order id' => [
                new OrderIdMessage('12345-678'),
                ['orderno' => '12345-678'],
            ],
            'order id with tracking' => [
                new GetStatusMessage('12345-678', tracking: true),
                ['orderno' => '12345-678', 'tracking' => true],
            ],
            'document request takes a list' => [
                new GetDocumentMessage(['12345-678', '12345-679']),
                ['orderno' => ['12345-678', '12345-679']],
            ],
            'address delete carries the role' => [
                new AddressDeleteMessage(id: '42', type: AddressTypeEnum::Sender),
                ['type' => 'SENDER', 'id' => '42'],
            ],
            'enums serialize as their backing value' => [
                new GetInvoicesMessage(InvoiceTypeEnum::InvoiceCorrection),
                ['type' => 'INVOICE_CORRECTION'],
            ],
            'enum inside a collection' => [
                new GetPointMessage(
                    couriers: ['INPOST_PACZKOMAT'],
                    functions: [PointFunctionEnum::Cod, PointFunctionEnum::Send],
                ),
                ['couriers' => ['INPOST_PACZKOMAT'], 'functions' => ['cod', 'send']],
            ],
            'nested objects' => [
                new ValidateCouponMessage(
                    couponCode: 'PROMO2026',
                    courier: 'UPS',
                    shipmentType: ShipmentTypeEnum::Box,
                    packs: [
                        new PackMessage(length: 30, width: 20, height: 15, weight: 2.5, type: PackTypeEnum::Standard),
                    ],
                ),
                [
                    'coupon_code' => 'PROMO2026',
                    'courier' => 'UPS',
                    'shipmenttype' => 'box',
                    'packs' => [[
                        'length' => 30, 'width' => 20, 'height' => 15,
                        'weight' => 2.5, 'type' => 'ST', 'amount' => 1,
                    ]],
                ],
            ],
            'cash on delivery requested' => [
                new CodMessage(
                    amount: 250.0,
                    bankAccount: 'PL34102010260000000000000001',
                    type: CodTypeEnum::Standard,
                    returnType: CodReturnTypeEnum::BankAccount,
                ),
                [
                    'codamount' => 250.0,
                    'codbankaccount' => 'PL34102010260000000000000001',
                    'codtype' => 'S',
                    'return_cod' => 'BA',
                ],
            ],
            'collection booked' => [
                new PickupMessage(
                    pickupDate: new \DateTimeImmutable('2026-09-24 00:00:00'),
                    pickupTimeFrom: new \DateTimeImmutable('2026-09-24 09:00:00'),
                    pickupTimeTo: new \DateTimeImmutable('2026-09-24 17:00:00'),
                ),
                [
                    'pickupdate' => '2026-09-24',
                    'pickuptimefrom' => '09:00',
                    'pickuptimeto' => '17:00',
                    'nocourierorder' => false,
                    'multiPickup' => false,
                ],
            ],
            'address book entry written' => [
                new AddressSaveMessage(
                    name: 'Magazyn',
                    person: 'Jan Kowalski',
                    street: 'Długa',
                    houseNumber: '12',
                    postcode: '61-001',
                    city: 'Poznań',
                    email: 'jan@example.com',
                    phone: '500600700',
                    type: AddressTypeEnum::Sender,
                ),
                [
                    'name' => 'Magazyn',
                    'person' => 'Jan Kowalski',
                    'street' => 'Długa',
                    'housenumber' => '12',
                    'postcode' => '61-001',
                    'city' => 'Poznań',
                    'email' => 'jan@example.com',
                    'phone' => '500600700',
                    'type' => 'SENDER',
                    'country' => 'PL',
                    'default' => false,
                    'is_cover_address' => false,
                ],
            ],
            'parcel template written' => [
                new PackTemplateSaveMessage(
                    name: 'Karton A',
                    length: 30,
                    width: 20,
                    height: 15,
                    weight: 2.5,
                    shipmentType: ShipmentTypeEnum::Box,
                ),
                [
                    'length' => 30,
                    'width' => 20,
                    'height' => 15,
                    'weight' => 2.5,
                    'type' => 'ST',
                    'name' => 'Karton A',
                    'shipmenttype' => 'box',
                    'COD' => 0.0,
                    'insurance' => 0.0,
                    'description' => '',
                    'services' => [],
                ],
            ],
            'bank account registered' => [
                new BankAccountSaveMessage(
                    name: 'Firmowe',
                    number: 'PL34102010260000000000000001',
                    default: true,
                ),
                [
                    'name' => 'Firmowe',
                    'number' => 'PL34102010260000000000000001',
                    'default' => true,
                ],
            ],
            'two factor namespace' => [
                new RegisterMessage(TwoFactorNamespaceEnum::ChangeIban),
                ['namespace' => 'CHANGE_BANK_ACCOUNT_NUMBER'],
            ],
            'regulations type' => [
                new GetRegulationsMessage(RegulationsTypeEnum::PrivacyPolicy),
                ['type' => 'privacy_policy'],
            ],
        ];
    }

    /**
     * @param object $message
     * @param array<string, mixed> $expected
     *
     * @return void
     */
    #[DataProvider('messagesAndTheirWireForm')]
    public function testMessageSerializesToTheKeysTheApiExpects(object $message, array $expected): void
    {
        /** @var array<string, mixed> $actual */
        $actual = \json_decode(
            (new SerializerProvider())->getSerializer()->serialize($message, 'json'),
            true,
            flags: \JSON_THROW_ON_ERROR
        );

        // Key order is not part of the contract — a JSON object is unordered, and the serializer
        // emits constructor order where the expectations read in whatever order documents the
        // message best. Everything else is compared exactly.
        self::assertSame(self::sortDeep($expected), self::sortDeep($actual));
    }

    /**
     * Dates are sent in the format each endpoint expects, which is not the same everywhere.
     *
     * @return void
     */
    public function testDatesUseThePerFieldFormatTheApiExpects(): void
    {
        /** @var array<string, mixed> $actual */
        $actual = \json_decode(
            (new SerializerProvider())->getSerializer()->serialize(
                new GetOrdersMessage(
                    dateFrom: new \DateTimeImmutable('2026-01-15 13:45:00'),
                    dateTo: new \DateTimeImmutable('2026-09-23 08:00:00'),
                ),
                'json'
            ),
            true,
            flags: \JSON_THROW_ON_ERROR
        );

        self::assertSame(['date_from' => '2026-01-15', 'date_to' => '2026-09-23'], $actual);
    }

    /**
     * Optional fields are left out rather than sent as null, which the API would read as a value.
     *
     * @return void
     */
    public function testUnsetOptionalFieldsAreOmittedEntirely(): void
    {
        /** @var array<string, mixed> $actual */
        $actual = \json_decode(
            (new SerializerProvider())->getSerializer()->serialize(new GetOrdersMessage(page: 2), 'json'),
            true,
            flags: \JSON_THROW_ON_ERROR
        );

        self::assertSame(['page' => 2], $actual);
    }

    /**
     * Collections of enums have to survive the round trip in both directions.
     *
     * @return void
     */
    public function testCollectionsOfEnumsDeserialize(): void
    {
        /** @var CourierPoint $point */
        $point = (new SerializerProvider())->getSerializer()->deserialize(
            (string)\json_encode(['id' => 'POZ01A', 'functions' => ['cod', 'send', 'collect']]),
            CourierPoint::class,
            'json'
        );

        self::assertSame('POZ01A', $point->getId());
        self::assertEquals(
            [PointFunctionEnum::Cod, PointFunctionEnum::Send, PointFunctionEnum::Collect],
            $point->getFunctions()
        );
    }
}
