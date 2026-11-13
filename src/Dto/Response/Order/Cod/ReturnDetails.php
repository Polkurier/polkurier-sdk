<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Order\Cod;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Order\CodReturnReasonEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * What happened to a collected cash-on-delivery amount.
 *
 * Present once the money has been settled, which may be long after delivery.
 *
 * @api
 */
final readonly class ReturnDetails implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_REASON = 'reason';
    public const string FIELD_DESCRIPTION = 'description';
    public const string FIELD_DOCUMENT_NUMBER = 'document_number';
    public const string FIELD_INVOICE_NUMBER = 'invoice_number';
    public const string FIELD_DATE = 'date';
    public const string FIELD_AMOUNT = 'amount';
    /**-@#*/

    /**
     * @param CodReturnReasonEnum|null $reason
     * @param string $description
     * @param float $amount
     * @param string|null $documentNumber
     * @param \DateTimeInterface|null $date
     * @param string[]|null $invoiceNumbers
     */
    public function __construct(
        /**
         * @var CodReturnReasonEnum|null null for a value the SDK doesn't know
         */
        #[Serializer\SerializedName(self::FIELD_REASON)]
        public ?CodReturnReasonEnum $reason,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        public string $description,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_AMOUNT)]
        public float $amount,

        /**
         * Reference of the document the amount was applied to, where one exists.
         *
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_DOCUMENT_NUMBER)]
        #[OmitWhenEmpty]
        public ?string $documentNumber = null,

        /**
         * When the amount was applied. Null until the settlement has a date.
         *
         * @var \DateTimeInterface|null
         */
        #[Serializer\SerializedName(self::FIELD_DATE)]
        #[OmitWhenEmpty]
        public ?\DateTimeInterface $date = null,

        /**
         * @var string[]|null null if the response leaves it out
         */
        #[Serializer\SerializedName(self::FIELD_INVOICE_NUMBER)]
        #[OmitWhenEmpty]
        public ?array $invoiceNumbers = null,
    ) {}
}
