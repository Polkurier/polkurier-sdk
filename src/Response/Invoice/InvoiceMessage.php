<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Invoice;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * One invoice on the account.
 *
 * The unpaid value is what is still to be settled. Part payments and compensations reduce it, so it
 * can be lower than the full value. The invoice is paid when it reaches zero.
 *
 * Both dates have no time component.
 */
readonly class InvoiceMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_NUMBER = 'number';
    public const string FIELD_VALUE = 'value';
    public const string FIELD_UNPAID_VALUE = 'unpaid_value';
    public const string FIELD_DATE = 'date';
    public const string FIELD_PAYMENT_DEADLINE = 'payment_deadline';
    /**-@#*/

    /**
     * @param string $number the invoice number as printed on the document
     * @param float $value gross total, in PLN
     * @param float $unpaidValue amount outstanding in PLN, zero when settled
     * @param ?\DateTimeInterface $date issue date
     * @param ?\DateTimeInterface $paymentDeadline due date
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NUMBER)]
        private string $number = '',

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_VALUE)]
        private float $value = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_UNPAID_VALUE)]
        private float $unpaidValue = 0.0,

        /**
         * @var ?\DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_DATE)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
        #[OmitWhenEmpty]
        private ?\DateTimeInterface $date = null,

        /**
         * @var ?\DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_PAYMENT_DEADLINE)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
        #[OmitWhenEmpty]
        private ?\DateTimeInterface $paymentDeadline = null,
    ) {}

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @return float
     */
    public function getValue(): float
    {
        return $this->value;
    }

    /**
     * @return float
     */
    public function getUnpaidValue(): float
    {
        return $this->unpaidValue;
    }

    /**
     * @return ?\DateTimeInterface the day, at midnight
     */
    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    /**
     * @return ?\DateTimeInterface the day, at midnight
     */
    public function getPaymentDeadline(): ?\DateTimeInterface
    {
        return $this->paymentDeadline;
    }
}
