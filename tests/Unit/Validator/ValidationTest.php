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
use Polkurier\Webservice\Dto\Request\Account\ChangeDefaultCourierMessage;
use Polkurier\Webservice\Dto\Request\Courier\GetPointMessage;
use Polkurier\Webservice\Dto\Request\Geo\FindCityMessage;
use Polkurier\Webservice\Dto\Request\Invoice\GetInvoicesMessage;
use Polkurier\Webservice\Dto\Request\Order\CancelOrderMessage;
use Polkurier\Webservice\Dto\Request\Order\CreateOrderMessage;
use Polkurier\Webservice\Dto\Request\Order\GetDocumentMessage;
use Polkurier\Webservice\Dto\Request\Order\GetOrdersMessage;
use Polkurier\Webservice\Dto\Request\Payment\RegisterPaymentMessage;
use Polkurier\Webservice\Dto\Request\System\ValidateCouponMessage;
use Polkurier\Webservice\Dto\Request\Ticket\CreateTicketMessageMessage;
use Polkurier\Webservice\Dto\Request\Ticket\TicketAttachmentMessage;
use Polkurier\Webservice\Entity\Order;
use Polkurier\Webservice\Entity\Order\Address;
use Polkurier\Webservice\Entity\Order\Cod;
use Polkurier\Webservice\Entity\Order\PackItem;
use Polkurier\Webservice\Enum\Invoice\InvoiceTypeEnum;
use Polkurier\Webservice\Enum\Order\CodReturnTypeEnum;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Enum\Payment\PaymentModeEnum;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Validator\DefaultValidatorRegistryFactory;
use Polkurier\Webservice\Validator\ValidatorRegistry;

/**
 * The rules applied to a payload before it leaves.
 *
 * Two obligations, and the second matters more than the first. Catching a bad payload saves a
 * round trip, which is useful. Rejecting a good one blocks a call the API would have accepted,
 * which is a defect in the SDK — so every rule is tested from both sides, and the valid cases are
 * the ones to be nervous about.
 */
#[CoversNothing]
final class ValidationTest extends TestCase
{
    private ValidatorRegistry $validator;

    protected function setUp(): void
    {
        $this->validator = DefaultValidatorRegistryFactory::create();
    }

    /**
     * Payloads the API accepts, which must therefore pass untouched.
     *
     * @return array<string, array{MessageInterface}>
     */
    public static function acceptablePayloads(): array
    {
        return [
            'a complete order' => [self::order()],
            'a ticket reply carrying only a file' => [new CreateTicketMessageMessage(
                ticketId: 't1',
                content: '',
                files: [TicketAttachmentMessage::fromContents('a.txt', 'x', 'text/plain')],
            )],
            'an order with no cash on delivery' => [self::order(cod: null)],
            'cash on delivery zeroed, which the API ignores' => [self::order(cod: new Cod(amount: 0.0))],
            // The API stops reading the block at a zero amount, so nothing beside it can be wrong.
            'cash on delivery zeroed, with a bank account that would not pass' => [
                self::order(cod: new Cod(amount: 0.0, bankAccount: 'not-an-iban')),
            ],
            'a recipient collecting from a point, with no street address' => [
                self::order(recipient: new Address(
                    person: '',
                    street: '',
                    city: '',
                    postcode: '',
                    email: '',
                    phone: '600700800',
                    pointId: 'POZ01A',
                )),
            ],
            'a recipient with no email, which only the sender needs' => [
                self::order(recipient: new Address(
                    person: 'Anna Nowak',
                    street: 'Krótka',
                    city: 'Warszawa',
                    postcode: '00-001',
                    email: '',
                    phone: '600700800',
                )),
            ],
            'a foreign address, whose postcode is not a Polish one' => [
                self::order(recipient: new Address(
                    person: 'Hans Müller',
                    street: 'Hauptstraße',
                    city: 'Berlin',
                    postcode: '10115',
                    email: 'hans@example.de',
                    phone: '600700800',
                    country: 'DE',
                )),
            ],
            'a bank account written with spaces' => [
                self::order(cod: new Cod(
                    amount: 250.0,
                    bankAccount: '58 1020 3958 0000 9202 0222 8609',
                    returnType: CodReturnTypeEnum::BankAccount,
                )),
            ],
            'cash on delivery sent by postal order, which needs no account' => [
                self::order(cod: new Cod(amount: 250.0, returnType: CodReturnTypeEnum::PostOffice)),
            ],
            'an order number' => [new CancelOrderMessage('12345-678')],
            'several order numbers' => [new GetDocumentMessage(['12345-678', '12345-679'])],
            'an unfiltered order listing' => [new GetOrdersMessage()],
            'the maximum page size' => [new GetOrdersMessage(pageSize: GetOrdersMessage::MAX_PAGE_SIZE)],
            // None of the order context is the "is this coupon valid at all" question.
            'a coupon checked on its own' => [new ValidateCouponMessage(couponCode: 'PROMO2026')],
            'a coupon checked against parcels' => [
                new ValidateCouponMessage(
                    couponCode: 'PROMO2026',
                    courier: 'UPS',
                    shipmentType: ShipmentTypeEnum::Box,
                    packs: [new PackItem(length: 30, width: 20, height: 15, weight: 2.5)],
                ),
            ],
            'a postcode lookup' => [new FindCityMessage('61-001')],
            'an empty default carrier, which clears it' => [new ChangeDefaultCourierMessage(courier: '')],
            'a prepaid top-up with an amount' => [
                new RegisterPaymentMessage(mode: PaymentModeEnum::Prepaid, amount: 100.0),
            ],
            // Every other mode settles a balance the API already knows, so no amount is needed.
            'an invoice payment with no amount' => [
                new RegisterPaymentMessage(mode: PaymentModeEnum::Invoices, invoiceNumbers: ['2520/06/2026/POL']),
            ],
            'an order payment with the order numbers' => [
                new RegisterPaymentMessage(mode: PaymentModeEnum::Orders, orderNumbers: ['12345-678']),
            ],
            'a balance payment, which needs no list' => [
                new RegisterPaymentMessage(mode: PaymentModeEnum::Balance),
            ],
            'a lookup outside Poland, which the API answers with an empty list' => [
                new FindCityMessage('10115', country: 'DE'),
            ],
        ];
    }

    /**
     * @param MessageInterface $payload
     *
     * @return void
     */
    #[DataProvider('acceptablePayloads')]
    public function testPayloadTheApiAcceptsIsNotRejected(MessageInterface $payload): void
    {
        $this->validator->validate($payload);

        $this->addToAssertionCount(1);
    }

    /**
     * Payloads the API rejects, paired with the field the report should name.
     *
     * @return array<string, array{MessageInterface, string}>
     */
    public static function rejectablePayloads(): array
    {
        return [
            'no carrier' => [self::order(courier: ''), 'courier'],
            'a ticket reply with neither text nor files' => [
                new CreateTicketMessageMessage(ticketId: 't1', content: ''),
                'content',
            ],
            'a ticket attachment without a media type' => [
                new CreateTicketMessageMessage(
                    ticketId: 't1',
                    content: 'x',
                    files: [new TicketAttachmentMessage(name: 'a.txt', file: 'eA==', type: '')],
                ),
                'files[0].type',
            ],
            'a point search naming no carriers' => [new GetPointMessage(couriers: []), 'couriers'],
            'an order with no parcels' => [self::order(packs: []), 'packs'],
            // Half an order context: the API refuses, naming whichever field it noticed first.
            'a coupon with parcels but no carrier' => [
                new ValidateCouponMessage(
                    couponCode: 'PROMO2026',
                    shipmentType: ShipmentTypeEnum::Box,
                    packs: [new PackItem(length: 30, width: 20, height: 15, weight: 2.5)],
                ),
                'courier',
            ],
            'a prepaid top-up with no amount' => [
                new RegisterPaymentMessage(mode: PaymentModeEnum::Prepaid),
                'amount',
            ],
            'a prepaid top-up of nothing' => [
                new RegisterPaymentMessage(mode: PaymentModeEnum::Prepaid, amount: 0.0),
                'amount',
            ],
            'an invoice payment with no invoice numbers' => [
                new RegisterPaymentMessage(mode: PaymentModeEnum::Invoices),
                'invoices',
            ],
            'an invoice payment with a blank invoice number' => [
                new RegisterPaymentMessage(mode: PaymentModeEnum::Invoices, invoiceNumbers: ['']),
                'invoices[0]',
            ],
            'an order payment with no order numbers' => [
                new RegisterPaymentMessage(mode: PaymentModeEnum::Orders),
                'orders',
            ],
            'an order payment with a malformed order number' => [
                new RegisterPaymentMessage(mode: PaymentModeEnum::Orders, orderNumbers: ['12345']),
                'orders[0]',
            ],
            'a parcel with no length' => [
                self::order(packs: [new PackItem(length: 0, width: 20, height: 15, weight: 2.5)]),
                'packs[0].length',
            ],
            'a parcel with no weight' => [
                self::order(packs: [new PackItem(length: 30, width: 20, height: 15, weight: 0.0)]),
                'packs[0].weight',
            ],
            'a sender with no email' => [
                self::order(sender: new Address(
                    person: 'Jan',
                    street: 'Długa',
                    city: 'Poznań',
                    postcode: '61-001',
                    email: '',
                    phone: '500600700',
                )),
                'sender.email',
            ],
            'a Polish postcode in the wrong format' => [
                self::order(sender: new Address(
                    person: 'Jan',
                    street: 'Długa',
                    city: 'Poznań',
                    postcode: '61001',
                    email: 'jan@example.com',
                    phone: '500600700',
                )),
                'sender.postcode',
            ],
            'cash on delivery to a bank account with no account' => [
                self::order(cod: new Cod(
                    amount: 250.0,
                    bankAccount: '',
                    returnType: CodReturnTypeEnum::BankAccount,
                )),
                'COD.codbankaccount',
            ],
            'a bank account of the wrong length' => [
                self::order(cod: new Cod(
                    amount: 250.0,
                    bankAccount: '5810203958',
                    returnType: CodReturnTypeEnum::BankAccount,
                )),
                'COD.codbankaccount',
            ],
            'a malformed order number' => [new CancelOrderMessage('nope'), 'orderno'],
            'one malformed number among several' => [
                new GetDocumentMessage(['12345-678', 'nope']),
                'orderno[1]',
            ],
            'no order numbers at all' => [new GetDocumentMessage([]), 'orderno'],
            'a page size above the maximum' => [new GetOrdersMessage(pageSize: 500), 'pagesize'],
            'page zero, when paging is 1-based' => [new GetOrdersMessage(page: 0), 'page'],
            'an inverted date range' => [
                new GetOrdersMessage(
                    dateFrom: new \DateTimeImmutable('2026-09-01'),
                    dateTo: new \DateTimeImmutable('2026-01-01'),
                ),
                'date_from',
            ],
            'a postcode that is not one' => [new FindCityMessage('nope'), 'postcode'],
        ];
    }

    /**
     * @param MessageInterface $payload
     * @param string $expectedField
     *
     * @return void
     */
    #[DataProvider('rejectablePayloads')]
    public function testInvalidPayloadIsRejectedNamingTheField(
        MessageInterface $payload,
        string $expectedField,
    ): void {
        try {
            $this->validator->validate($payload);
            self::fail(\sprintf('Expected a ValidationException naming "%s".', $expectedField));
        } catch (ValidationException $e) {
            self::assertArrayHasKey(
                $expectedField,
                $e->getValidationErrors(),
                \sprintf(
                    'Expected "%s" among the reported fields, got: %s',
                    $expectedField,
                    \implode(', ', \array_keys($e->getValidationErrors()))
                )
            );
            self::assertNotEmpty($e->getFieldErrors($expectedField));
        }
    }

    /**
     * Everything wrong with a payload is reported together, the way the API reports its own
     * validation, so a caller fixing one does not then discover the next.
     *
     * @return void
     */
    public function testEveryFailureIsReportedAtOnce(): void
    {
        try {
            $this->validator->validate(self::order(
                courier: '',
                packs: [new PackItem(length: 0, width: 0, height: 15, weight: 2.5)],
            ));
            self::fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            self::assertSame(
                ['courier', 'packs[0].length', 'packs[0].width'],
                \array_keys($e->getValidationErrors())
            );
        }
    }

    /**
     * A missing field is reported once, not once per rule that requires it.
     *
     * @return void
     */
    public function testEachViolationIsReportedOnce(): void
    {
        try {
            $this->validator->validate(self::order(sender: new Address(
                person: 'Jan Kowalski',
                street: 'Długa',
                city: 'Poznań',
                postcode: '61-001',
                email: 'jan@example.com',
                phone: '',
                houseNumber: '12',
            )));
            self::fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            self::assertSame(['sender.phone'], \array_keys($e->getValidationErrors()));
            self::assertCount(1, $e->getFieldErrors('sender.phone'));
        }
    }

    /**
     * The API doesn't read the coupon's sender, so a malformed one passes.
     *
     * @return void
     */
    public function testCouponCheckIgnoresTheSender(): void
    {
        $this->validator->validate(new ValidateCouponMessage(
            couponCode: 'SPRING10',
            sender: new Address(
                person: '',
                street: '',
                city: '',
                postcode: 'not a postcode',
                email: 'not an email',
                phone: '',
            ),
        ));

        $this->addToAssertionCount(1);
    }

    /**
     * The page size limit of a paged endpoint, and one report per field when a value is too small.
     *
     * @return void
     */
    public function testAPagedEndpointEnforcesItsPageSizeLimit(): void
    {
        foreach ([0, GetOrdersMessage::MAX_PAGE_SIZE + 1] as $pageSize) {
            try {
                $this->validator->validate(new GetOrdersMessage(pageSize: $pageSize));
                self::fail('Expected a ValidationException.');
            } catch (ValidationException $e) {
                self::assertCount(1, $e->getFieldErrors('pagesize'), (string)$pageSize);
            }
        }

        $this->validator->validate(new GetOrdersMessage(pageSize: GetOrdersMessage::MAX_PAGE_SIZE));
        $this->validator->validate(new GetInvoicesMessage(type: InvoiceTypeEnum::cases()[0], pageSize: 1000));
    }

    /**
     * List entries of the wrong type are reported against their index.
     *
     * @return void
     */
    public function testListEntriesOfTheWrongTypeAreReported(): void
    {
        try {
            // @phpstan-ignore argument.type, argument.type (the wrong entry types are the point)
            $this->validator->validate(new GetPointMessage(couriers: ['INPOST', 7], functions: ['send']));
            self::fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            self::assertSame(['couriers[1]', 'functions[0]'], \array_keys($e->getValidationErrors()));
        }

        try {
            // @phpstan-ignore argument.type
            $this->validator->validate(self::order(packs: ['not a pack']));
            self::fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('packs[0]', $e->getValidationErrors());
        }
    }

    /**
     * The keys are a contract of their own: integrations map them onto their own form fields, so
     * a release keeps the wire names, the dot between a record and its field, the `[n]` of a list
     * entry, and the absence of a prefix for a record the payload inlines.
     *
     * @see \Polkurier\Webservice\Exception\ValidationException
     *
     * @return void
     */
    public function testTheKeysNameTheFieldTheWayTheApiDoes(): void
    {
        $order = self::order(
            packs: [
                new PackItem(length: 30, width: 20, height: 15, weight: 2.5, type: PackTypeEnum::Standard),
                new PackItem(length: 30, width: 20, height: 15, weight: -5.0, type: PackTypeEnum::Standard),
            ],
            sender: new Address(
                person: 'Jan Kowalski',
                street: 'Długa',
                city: 'Poznań',
                postcode: 'not a postcode',
                email: 'jan@example.com',
                phone: '500600700',
                houseNumber: '12',
            ),
        );

        try {
            $this->validator->validate($order);
            self::fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            // `packs`, `weight`, `sender` and `postcode` are the API's names, not the SDK's; the
            // order is inlined into create_order, so its fields carry no prefix of their own.
            self::assertSame(
                ['packs[1].weight', 'sender.postcode'],
                \array_keys($e->getValidationErrors()),
            );
        }
    }

    /**
     * A message with no rule registered passes: silence means "not checked", not "approved".
     *
     * @return void
     */
    public function testMessageWithNoRegisteredRulePasses(): void
    {
        (new ValidatorRegistry())->validate(new CancelOrderMessage('anything at all'));

        $this->addToAssertionCount(1);
    }

    /**
     * @param string $courier
     * @param PackItem[]|null $packs
     * @param Address|null $sender
     * @param Address|null $recipient
     * @param Cod|null $cod
     *
     * @return CreateOrderMessage
     */
    private static function order(
        string $courier = 'UPS',
        ?array $packs = null,
        ?Address $sender = null,
        ?Address $recipient = null,
        ?Cod $cod = null,
    ): CreateOrderMessage {
        return new CreateOrderMessage(new Order(
            courier: $courier,
            shipmentType: ShipmentTypeEnum::Box,
            packs: $packs ?? [
                new PackItem(length: 30, width: 20, height: 15, weight: 2.5, type: PackTypeEnum::Standard),
            ],
            sender: $sender ?? new Address(
                person: 'Jan Kowalski',
                street: 'Długa',
                city: 'Poznań',
                postcode: '61-001',
                email: 'jan@example.com',
                phone: '500600700',
                houseNumber: '12',
            ),
            recipient: $recipient ?? new Address(
                person: 'Anna Nowak',
                street: 'Krótka',
                city: 'Warszawa',
                postcode: '00-001',
                email: 'anna@example.com',
                phone: '600700800',
                houseNumber: '5',
            ),
            cod: $cod,
        ));
    }
}
