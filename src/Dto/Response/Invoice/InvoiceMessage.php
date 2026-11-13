<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Invoice;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * One invoice or invoice correction on the account.
 *
 * The unpaid value is what is still to be settled after part payments and compensations. The
 * invoice is paid when it reaches zero.
 *
 * A correction carries only number, value and date; its unpaid value reads as zero either way.
 *
 * Both dates have no time component.
 *
 * @api
 */
final readonly class InvoiceMessage implements MessageInterface
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
     * @param float $unpaidValue amount outstanding in PLN, zero when settled, and always zero for
     *        a correction
     * @param \DateTimeInterface|null $date issue date
     * @param \DateTimeInterface|null $paymentDeadline due date, always null for a correction
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NUMBER)]
        public string $number = '',

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_VALUE)]
        public float $value = 0.0,

        /**
         * @var float zero when settled, and for a correction
         */
        #[Serializer\SerializedName(self::FIELD_UNPAID_VALUE)]
        public float $unpaidValue = 0.0,

        /**
         * @var \DateTimeInterface|null the day, at midnight
         */
        #[Serializer\SerializedName(self::FIELD_DATE)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
        #[OmitWhenEmpty]
        public ?\DateTimeInterface $date = null,

        /**
         * @var \DateTimeInterface|null the day, at midnight; null for a correction
         */
        #[Serializer\SerializedName(self::FIELD_PAYMENT_DEADLINE)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
        #[OmitWhenEmpty]
        public ?\DateTimeInterface $paymentDeadline = null,
    ) {}
}
