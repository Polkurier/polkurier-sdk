<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Message;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Dto\Response\Account\AccountInfoMessage;
use Polkurier\Webservice\Dto\Response\Account\AccountVerificationMessage;
use Polkurier\Webservice\Dto\Response\MoneyBox\MoneyBoxFeeComponentMessage;
use Polkurier\Webservice\Dto\Response\Order\OrderStatusMessage;
use Polkurier\Webservice\Dto\Response\Order\TrackingEventMessage;
use Polkurier\Webservice\Dto\Response\Payment\PaymentInfoMessage;
use Polkurier\Webservice\Dto\Response\Payment\RegisteredPaymentMessage;
use Polkurier\Webservice\Dto\Response\System\CouponValidationMessage;
use Polkurier\Webservice\Entity\Order;
use Polkurier\Webservice\Enum\Account\AccountPaymentTypeEnum;
use Polkurier\Webservice\Enum\Account\AccountVerificationStatusEnum;
use Polkurier\Webservice\Enum\Account\AccountVerificationTypeEnum;
use Polkurier\Webservice\Enum\Coupon\CouponTypeEnum;
use Polkurier\Webservice\Enum\Coupon\CouponUsageTypeEnum;
use Polkurier\Webservice\Enum\MoneyBox\MoneyBoxFeeComponentTypeEnum;
use Polkurier\Webservice\Enum\Order\OrderStatusCodeEnum;
use Polkurier\Webservice\Enum\Payment\PaymentStateEnum;
use Polkurier\Webservice\Enum\Serializer\ResponseDriftTypeEnum;
use Polkurier\Webservice\Test\Support\Responses;

/**
 * The fields whose values the API draws from a closed set are typed as that set.
 *
 * Each is read leniently: a value the set does not hold reads as null with the difference
 * recorded, so one added to the API later does not make the response unreadable.
 */
#[CoversNothing]
final class ClosedSetFieldsTest extends TestCase
{
    /**
     * The same eight codes reach an order three ways, so one enum serves all three.
     *
     * @param string $code
     *
     * @return void
     */
    #[DataProvider('statusCodes')]
    public function testAnOrderStatusCodeReadsAsItsCaseWhereverItArrives(string $code): void
    {
        $case = OrderStatusCodeEnum::from($code);

        $order = Responses::read(Order::class, [Order::FIELD_COURIER => 'DPD', Order::FIELD_STATUS_CODE => $code]);
        $status = Responses::read(OrderStatusMessage::class, [OrderStatusMessage::FIELD_STATUS_CODE => $code]);
        $event = Responses::read(TrackingEventMessage::class, [TrackingEventMessage::FIELD_STATUS_CODE => $code]);

        self::assertInstanceOf(Order::class, $order);
        self::assertInstanceOf(OrderStatusMessage::class, $status);
        self::assertInstanceOf(TrackingEventMessage::class, $event);
        self::assertSame($case, $order->getStatusCode());
        self::assertSame($case, $status->statusCode);
        self::assertSame($case, $event->statusCode);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function statusCodes(): array
    {
        return [
            'placed, not confirmed' => ['O'],
            'confirmed' => ['P'],
            'cancelled' => ['A'],
            'in transit' => ['WP'],
            'delivered' => ['D'],
            'awaiting pickup' => ['OC'],
            'returning' => ['Z'],
            'problem reported' => ['W'],
        ];
    }

    /**
     * @param class-string $class
     * @param string $field
     * @param string $property
     * @param string $value
     * @param \UnitEnum $expected
     *
     * @return void
     */
    #[DataProvider('closedSets')]
    public function testAClosedSetFieldReadsAsItsCase(
        string $class,
        string $field,
        string $property,
        string $value,
        \UnitEnum $expected,
    ): void {
        $read = Responses::read($class, [$field => $value]);

        self::assertInstanceOf($class, $read);
        self::assertSame($expected, $read->{$property});
    }

    /**
     * A value outside the set reads as null, with the difference recorded against the field's own
     * path, rather than making the response unreadable.
     *
     * @param class-string $class
     * @param string $field
     * @param string $property
     *
     * @return void
     */
    #[DataProvider('closedSets')]
    public function testAValueOutsideTheSetReadsAsNullAndIsRecorded(
        string $class,
        string $field,
        string $property,
    ): void {
        $wrapped = Responses::readWrapped($class, [$field => 'SOMETHING_NEW']);

        $read = $wrapped->getEnvelope()->response;
        self::assertInstanceOf($class, $read);
        self::assertNull($read->{$property});
        self::assertSame(
            [[ResponseDriftTypeEnum::UnknownEnumValue->value, $property, 'SOMETHING_NEW']],
            \array_map(
                static fn($drift): array => [$drift->type->value, $drift->path, $drift->value],
                $wrapped->getDrift(),
            ),
        );
    }

    /**
     * @return array<string, array{class-string, string, string, string, \UnitEnum}>
     */
    public static function closedSets(): array
    {
        return [
            'a verification by online payment' => [
                AccountVerificationMessage::class,
                AccountVerificationMessage::FIELD_TYPE,
                'type',
                'online',
                AccountVerificationTypeEnum::Online,
            ],
            'a verification by bank transfer' => [
                AccountVerificationMessage::class,
                AccountVerificationMessage::FIELD_TYPE,
                'type',
                'bank_transfer',
                AccountVerificationTypeEnum::BankTransfer,
            ],
            'a verification nobody has paid for yet' => [
                AccountVerificationMessage::class,
                AccountVerificationMessage::FIELD_STATUS,
                'status',
                'NEW',
                AccountVerificationStatusEnum::New,
            ],
            'a verification whose payment is on its way' => [
                AccountVerificationMessage::class,
                AccountVerificationMessage::FIELD_STATUS,
                'status',
                'PAYMENT_PLACED',
                AccountVerificationStatusEnum::PaymentPlaced,
            ],
            'a verification whose payment arrived' => [
                AccountVerificationMessage::class,
                AccountVerificationMessage::FIELD_STATUS,
                'status',
                'PAYMENT_RECEIVED',
                AccountVerificationStatusEnum::PaymentReceived,
            ],
            'a verification that could not be decided' => [
                AccountVerificationMessage::class,
                AccountVerificationMessage::FIELD_STATUS,
                'status',
                'INCONCLUSIVE',
                AccountVerificationStatusEnum::Inconclusive,
            ],
            'a verified account' => [
                AccountVerificationMessage::class,
                AccountVerificationMessage::FIELD_STATUS,
                'status',
                'VERIFIED',
                AccountVerificationStatusEnum::Verified,
            ],
            'an abandoned verification' => [
                AccountVerificationMessage::class,
                AccountVerificationMessage::FIELD_STATUS,
                'status',
                'CANCELLED',
                AccountVerificationStatusEnum::Cancelled,
            ],
            'a payout handling fee' => [
                MoneyBoxFeeComponentMessage::class,
                MoneyBoxFeeComponentMessage::FIELD_TYPE,
                'type',
                'PAYOUT_FEE',
                MoneyBoxFeeComponentTypeEnum::PayoutFee,
            ],
            'a postal order fee' => [
                MoneyBoxFeeComponentMessage::class,
                MoneyBoxFeeComponentMessage::FIELD_TYPE,
                'type',
                'POSTAL_TRANSFER_FEE',
                MoneyBoxFeeComponentTypeEnum::PostalTransferFee,
            ],
            'a payment with no provider operation yet' => [
                PaymentInfoMessage::class,
                PaymentInfoMessage::FIELD_STATE,
                'state',
                'NEW',
                PaymentStateEnum::New,
            ],
            'a payment waiting for the customer' => [
                PaymentInfoMessage::class,
                PaymentInfoMessage::FIELD_STATE,
                'state',
                'REGISTERED',
                PaymentStateEnum::Registered,
            ],
            'a payment being settled' => [
                PaymentInfoMessage::class,
                PaymentInfoMessage::FIELD_STATE,
                'state',
                'PENDING',
                PaymentStateEnum::Pending,
            ],
            'a payment that is paid' => [
                PaymentInfoMessage::class,
                PaymentInfoMessage::FIELD_STATE,
                'state',
                'DONE',
                PaymentStateEnum::Done,
            ],
            'a freshly registered payment, read as the other class' => [
                RegisteredPaymentMessage::class,
                RegisteredPaymentMessage::FIELD_STATE,
                'state',
                'REGISTERED',
                PaymentStateEnum::Registered,
            ],
            'an account billed on an invoice' => [
                AccountInfoMessage::class,
                AccountInfoMessage::FIELD_PAYMENT_TYPE,
                'paymentType',
                'INVOICE',
                AccountPaymentTypeEnum::Invoice,
            ],
            'an account paying from prepaid' => [
                AccountInfoMessage::class,
                AccountInfoMessage::FIELD_PAYMENT_TYPE,
                'paymentType',
                'PREPAID',
                AccountPaymentTypeEnum::Prepaid,
            ],
            'a coupon taking an amount off' => [
                CouponValidationMessage::class,
                CouponValidationMessage::FIELD_TYPE,
                'type',
                'VALUE',
                CouponTypeEnum::Value,
            ],
            'a coupon taking a percentage off' => [
                CouponValidationMessage::class,
                CouponValidationMessage::FIELD_TYPE,
                'type',
                'PERCENT',
                CouponTypeEnum::Percent,
            ],
            'a coupon setting a fixed price' => [
                CouponValidationMessage::class,
                CouponValidationMessage::FIELD_TYPE,
                'type',
                'STATIC',
                CouponTypeEnum::Static,
            ],
            'a coupon usable once in total' => [
                CouponValidationMessage::class,
                CouponValidationMessage::FIELD_USAGE_TYPE,
                'usageType',
                'SINGLE',
                CouponUsageTypeEnum::Single,
            ],
            'a coupon usable once per customer' => [
                CouponValidationMessage::class,
                CouponValidationMessage::FIELD_USAGE_TYPE,
                'usageType',
                'MULTI',
                CouponUsageTypeEnum::Multi,
            ],
        ];
    }

    /**
     * `validate_coupon_code` leaves both fields out when the code matches no coupon.
     *
     * @return void
     */
    public function testACouponThatMatchedNothingHasNeitherTypeNorUsageType(): void
    {
        $read = Responses::read(CouponValidationMessage::class, [CouponValidationMessage::FIELD_VALID => false]);

        self::assertInstanceOf(CouponValidationMessage::class, $read);
        self::assertNull($read->usageType);
        self::assertNull($read->type);
    }
}
