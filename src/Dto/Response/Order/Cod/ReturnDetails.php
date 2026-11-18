<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Order\Cod;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Order\CodReturnReasonEnum;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

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

        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        public string $description,

        #[Serializer\SerializedName(self::FIELD_AMOUNT)]
        public float $amount,

        /**
         * Reference of the document the amount was applied to, where one exists.
         */
        #[Serializer\SerializedName(self::FIELD_DOCUMENT_NUMBER)]
        public ?string $documentNumber = null,

        /**
         * When the amount was applied. Null until the settlement has a date.
         */
        #[Serializer\SerializedName(self::FIELD_DATE)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => \DateTimeInterface::ATOM])]
        public ?\DateTimeInterface $date = null,

        /**
         * @var string[]|null null if the response leaves it out
         */
        #[Serializer\SerializedName(self::FIELD_INVOICE_NUMBER)]
        public ?array $invoiceNumbers = null,
    ) {}
}
