<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Order\Cod;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Order\CodReturnReasonEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * What happened to a collected cash-on-delivery amount.
 *
 * Present once the money has been settled, which may be long after delivery.
 */
readonly class ReturnDetails implements MessageInterface
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
     * @param array|null $invoiceNumbers
     */
    public function __construct(
        /**
         * @var CodReturnReasonEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_REASON)]
        private ?CodReturnReasonEnum $reason,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        private string $description,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_AMOUNT)]
        private float $amount,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_DOCUMENT_NUMBER)]
        #[OmitWhenEmpty]
        private ?string $documentNumber = null,

        /**
         * @var \DateTimeInterface|null
         */
        #[Serializer\SerializedName(self::FIELD_DATE)]
        #[OmitWhenEmpty]
        private ?\DateTimeInterface $date = null,

        /**
         * @var string[]|null
         */
        #[Serializer\SerializedName(self::FIELD_INVOICE_NUMBER)]
        #[OmitWhenEmpty]
        private ?array $invoiceNumbers = null,
    ) {}

    /**
     * @return CodReturnReasonEnum|null null for a value this version of the SDK doesn't know
     */
    public function getReason(): ?CodReturnReasonEnum
    {
        return $this->reason;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Reference of the document the amount was applied to, where one exists.
     *
     * @return string|null
     */
    public function getDocumentNumber(): ?string
    {
        return $this->documentNumber;
    }

    /**
     * @return string[]|null null if the response leaves it out
     */
    public function getInvoiceNumbers(): ?array
    {
        return $this->invoiceNumbers;
    }

    /**
     * When the amount was applied. Null until the settlement has a date.
     *
     * @return \DateTimeInterface|null
     */
    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }
}
