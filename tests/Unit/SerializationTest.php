<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Dto\Request\Account\ChangeDefaultCourierMessage;
use Polkurier\Webservice\Dto\Request\Account\GetRegulationsMessage;
use Polkurier\Webservice\Dto\Request\Account\SavePayerAddressMessage;
use Polkurier\Webservice\Dto\Request\Address\DeleteMessage as AddressDeleteMessage;
use Polkurier\Webservice\Dto\Request\BankAccount\SaveBankAccountMessage;
use Polkurier\Webservice\Dto\Request\Contact\SaveContactFormMessage;
use Polkurier\Webservice\Dto\Request\Courier\GetPointMessage;
use Polkurier\Webservice\Dto\Request\Invoice\GetInvoicesMessage;
use Polkurier\Webservice\Dto\Request\Invoice\GetInvoiceSummaryListMessage;
use Polkurier\Webservice\Dto\Request\Order\CancelOrderMessage;
use Polkurier\Webservice\Dto\Request\Order\GetDocumentMessage;
use Polkurier\Webservice\Dto\Request\Order\GetOrdersMessage;
use Polkurier\Webservice\Dto\Request\Order\GetStatusMessage;
use Polkurier\Webservice\Dto\Request\Order\MultiPickupAvailabilityRequestMessage;
use Polkurier\Webservice\Dto\Request\System\ValidateCouponMessage;
use Polkurier\Webservice\Dto\Request\Ticket\CreateTicketMessage;
use Polkurier\Webservice\Dto\Request\Ticket\CreateTicketMessageMessage;
use Polkurier\Webservice\Dto\Request\Ticket\TicketAttachmentMessage;
use Polkurier\Webservice\Dto\Request\TwoFactorToken\RegisterMessage;
use Polkurier\Webservice\Dto\Response\CourierPoint;
use Polkurier\Webservice\Entity\Address;
use Polkurier\Webservice\Entity\BankAccount;
use Polkurier\Webservice\Entity\Order;
use Polkurier\Webservice\Entity\Order\Address as OrderAddress;
use Polkurier\Webservice\Entity\Order\Cod;
use Polkurier\Webservice\Entity\Order\PackItem;
use Polkurier\Webservice\Entity\Order\Pickup;
use Polkurier\Webservice\Entity\PackTemplate;
use Polkurier\Webservice\Enum\Account\PayerAddressTypeEnum;
use Polkurier\Webservice\Enum\Account\RegulationsTypeEnum;
use Polkurier\Webservice\Enum\Address\AddressTypeEnum;
use Polkurier\Webservice\Enum\Courier\PointFunctionEnum;
use Polkurier\Webservice\Enum\Invoice\InvoiceSummaryFormatEnum;
use Polkurier\Webservice\Enum\Invoice\InvoiceTypeEnum;
use Polkurier\Webservice\Enum\Order\CodReturnTypeEnum;
use Polkurier\Webservice\Enum\Order\CodTypeEnum;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Enum\TwoFactorToken\TwoFactorNamespaceEnum;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Test\Support\Sample;

/**
 * The keys and values the SDK actually puts on the wire.
 *
 * The API matches field names exactly and silently ignores anything it does not recognise, so a
 * renamed key is not an error — it is a parameter that quietly never arrives, e.g. `orderno` sent
 * as `order_no`. Every message whose keys matter is therefore asserted against the literal JSON.
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
                new Cod(amount: 100.0),
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
                [
                    'category' => 'wspolpraca',
                    'form_id' => 'f-1',
                    'nonce' => 'n-1',
                    'email' => 'jan@example.com',
                    'consent' => true,
                ],
            ],
            'a contact form files answer, a list of attachments' => [
                new SaveContactFormMessage(
                    category: 'wspolpraca',
                    formId: 'f-1',
                    nonce: 'n-1',
                    fields: ['files' => [TicketAttachmentMessage::makeFromContents('a.txt', "hello\n", 'text/plain')]],
                ),
                [
                    'category' => 'wspolpraca',
                    'form_id' => 'f-1',
                    'nonce' => 'n-1',
                    'files' => [['name' => 'a.txt', 'file' => 'aGVsbG8K', 'type' => 'text/plain']],
                ],
            ],
            'a contact form answer never replaces a fixed key' => [
                new SaveContactFormMessage(
                    category: 'wspolpraca',
                    formId: 'f-1',
                    nonce: 'n-1',
                    fields: ['nonce' => 'forged'],
                ),
                ['category' => 'wspolpraca', 'form_id' => 'f-1', 'nonce' => 'n-1'],
            ],
            'order id' => [
                new CancelOrderMessage('12345-678'),
                ['orderno' => '12345-678'],
            ],
            'order id with tracking' => [
                new GetStatusMessage('12345-678', tracking: true),
                ['orderno' => '12345-678', 'tracking' => true],
            ],
            'multi pickup availability, camelCase keys' => [
                new MultiPickupAvailabilityRequestMessage(
                    courier: 'DPD',
                    packs: [new PackItem(length: 40, width: 30, height: 20, weight: 0.55)],
                    senderPostCode: '31-042',
                    shipmentType: ShipmentTypeEnum::Box,
                ),
                [
                    'courier' => 'DPD',
                    'packs' => [
                        [
                            'length' => 40, 'width' => 30, 'height' => 20, 'weight' => 0.55,
                            'type' => 'ST', 'amount' => 1,
                        ],
                    ],
                    'senderPostCode' => '31-042',
                    'shipmentType' => 'box',
                ],
            ],
            'multi pickup availability without the optional fields' => [
                new MultiPickupAvailabilityRequestMessage(
                    courier: 'DPD',
                    packs: [new PackItem(length: 40, width: 30, height: 20, weight: 0.55)],
                ),
                [
                    'courier' => 'DPD',
                    'packs' => [
                        [
                            'length' => 40, 'width' => 30, 'height' => 20, 'weight' => 0.55,
                            'type' => 'ST', 'amount' => 1,
                        ],
                    ],
                ],
            ],
            'invoice summary, PDF unless told otherwise' => [
                new GetInvoiceSummaryListMessage('2520/06/2026/POL'),
                ['number' => '2520/06/2026/POL', 'format' => 'PDF'],
            ],
            'invoice summary as CSV' => [
                new GetInvoiceSummaryListMessage('2520/06/2026/POL', InvoiceSummaryFormatEnum::Csv),
                ['number' => '2520/06/2026/POL', 'format' => 'CSV'],
            ],
            'ticket attachments, a list of files' => [
                new CreateTicketMessage(
                    topicId: '5',
                    content: 'Hi',
                    files: [TicketAttachmentMessage::makeFromContents('notatka.txt', "hello\n", 'text/plain')],
                ),
                [
                    'topic_id' => '5',
                    'content' => 'Hi',
                    'name' => '',
                    'files' => [['name' => 'notatka.txt', 'file' => 'aGVsbG8K', 'type' => 'text/plain']],
                ],
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
                        new PackItem(length: 30, width: 20, height: 15, weight: 2.5, type: PackTypeEnum::Standard),
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
                new Cod(
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
                new Pickup(
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
                new Address(
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
                new PackTemplate(
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
                new SaveBankAccountMessage(new BankAccount(
                    name: 'Firmowe',
                    number: 'PL34102010260000000000000001',
                    default: true,
                )),
                [
                    'name' => 'Firmowe',
                    'number' => 'PL34102010260000000000000001',
                    'default' => true,
                ],
            ],
            'two factor namespace' => [
                new RegisterMessage(TwoFactorNamespaceEnum::ChangeBankAccountNumber),
                ['namespace' => 'CHANGE_BANK_ACCOUNT_NUMBER'],
            ],
            'default carrier cleared' => [
                new ChangeDefaultCourierMessage(courier: ''),
                ['courier' => ''],
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
     * `create_order` reads the cover address from the keys of
     * {@see \Polkurier\Webservice\Serializer\RequestQuirksNormalizer::COVER_ADDRESS} alone, and the
     * flat number under `flat_number` instead of `flatnumber`. Any other key makes the API treat
     * the cover address as given and charge for printing it, so an address used as the cover
     * address goes out trimmed, country and point included.
     *
     * @return void
     */
    public function testACoverAddressSendsOnlyTheKeysTheOrderBlockIsReadFrom(): void
    {
        $order = new Order(
            courier: 'DPD',
            shipmentType: ShipmentTypeEnum::Box,
            packs: [Sample::of(PackItem::class)],
            sender: Sample::of(OrderAddress::class),
            recipient: Sample::of(OrderAddress::class),
            coverAddress: new OrderAddress(
                person: 'Jan Kowalski',
                street: 'Polna',
                city: 'Poznań',
                postcode: '61-001',
                houseNumber: '5',
                flatNumber: '2',
                pointId: 'POZ01A',
            ),
        );

        $payload = \json_decode(
            (new SerializerProvider())->getSerializer()->serialize($order, 'json'),
            true,
            flags: \JSON_THROW_ON_ERROR
        );

        $cover = $payload[Order::FIELD_COVER_ADDRESS] ?? [];
        self::assertSame(self::sortDeep([
            'person' => 'Jan Kowalski',
            'street' => 'Polna',
            'city' => 'Poznań',
            'postcode' => '61-001',
            'housenumber' => '5',
            'flat_number' => '2',
        ]), self::sortDeep($cover));
    }

    /**
     * Dates are sent in the format each endpoint expects, which is not the same everywhere.
     *
     * @return void
     */
    public function testDatesUseThePerFieldFormatTheApiExpects(): void
    {
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
        $actual = \json_decode(
            (new SerializerProvider())->getSerializer()->serialize(new GetOrdersMessage(page: 2), 'json'),
            true,
            flags: \JSON_THROW_ON_ERROR
        );

        self::assertSame(['page' => 2], $actual);
    }

    /**
     * The external ids go out only when set, so an order placed without them looks as before.
     */
    public function testAnOrderSendsItsExternalIdsOnlyWhenTheyAreSet(): void
    {
        $order = static fn(
            ?string $applicationId,
            ?string $orderId,
            ?string $orderNumber,
        ): Order => new Order(
            courier: 'DPD',
            shipmentType: ShipmentTypeEnum::Box,
            packs: [Sample::of(PackItem::class)],
            sender: Sample::of(OrderAddress::class),
            recipient: Sample::of(OrderAddress::class),
            externalApplicationId: $applicationId,
            externalOrderId: $orderId,
            externalOrderNumber: $orderNumber,
        );
        $serializer = (new SerializerProvider())->getSerializer();

        $without = \json_decode($serializer->serialize($order(null, null, null), 'json'), true);
        $with = \json_decode($serializer->serialize($order('6f1c…-uuid', 'SHOP-1001', '1001'), 'json'), true);

        self::assertArrayNotHasKey('external_application_id', $without);
        self::assertArrayNotHasKey('external_order_id', $without);
        self::assertArrayNotHasKey('external_order_number', $without);
        self::assertSame('6f1c…-uuid', $with['external_application_id']);
        self::assertSame('SHOP-1001', $with['external_order_id']);
        self::assertSame('1001', $with['external_order_number']);
    }

    /**
     * @return array<string, array{PackTypeEnum}>
     */
    public static function packTypes(): array
    {
        $cases = [];
        foreach (PackTypeEnum::cases() as $type) {
            $cases[$type->name] = [$type];
        }

        return $cases;
    }

    /**
     * A response carrying any pack type can be read.
     */
    #[DataProvider('packTypes')]
    public function testEveryPackTypeDeserializes(PackTypeEnum $type): void
    {
        /** @var PackItem $pack */
        $pack = (new SerializerProvider())->getSerializer()->deserialize(
            (string)\json_encode(
                ['length' => 120, 'width' => 80, 'height' => 100, 'weight' => 50, 'type' => $type->value],
            ),
            PackItem::class,
            'json'
        );

        self::assertSame($type, $pack->getType());
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

        self::assertSame('POZ01A', $point->id);
        self::assertEquals(
            [PointFunctionEnum::Cod, PointFunctionEnum::Send, PointFunctionEnum::Collect],
            $point->functions
        );
    }
}
