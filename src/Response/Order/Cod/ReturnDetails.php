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
class ReturnDetails implements MessageInterface
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
     * @param CodReturnReasonEnum $reason
     * @param string $description
     * @param float $amount
     * @param string|null $documentNumber
     * @param \DateTimeInterface|null $date
     * @param array|null $invoiceNumbers
     */
    public function __construct(
        /**
         * @var CodReturnReasonEnum
         */
        #[Serializer\SerializedName(self::FIELD_REASON)]
        private CodReturnReasonEnum $reason,

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
     * @return CodReturnReasonEnum
     */
    public function getReason(): CodReturnReasonEnum
    {
        return $this->reason;
    }

    /**
     * @param CodReturnReasonEnum $reason
     *
     * @return void
     */
    public function setReason(CodReturnReasonEnum $reason): void
    {
        $this->reason = $reason;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return void
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
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
     * @param string|null $documentNumber
     *
     * @return void
     */
    public function setDocumentNumber(?string $documentNumber): void
    {
        $this->documentNumber = $documentNumber;
    }

    /**
     * @return string[]|null
     */
    public function getInvoiceNumbers(): ?array
    {
        return $this->invoiceNumbers;
    }

    /**
     * @param string[]|null $invoiceNumbers
     *
     * @return void
     */
    public function setInvoiceNumbers(?array $invoiceNumbers): void
    {
        $this->invoiceNumbers = $invoiceNumbers;
    }

    /**
      * When the amount was applied. Null while a settlement is recorded but not yet dated.
      *
     * @return \DateTimeInterface|null
     */
    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    /**
     * @param \DateTimeInterface|null $date
     *
     * @return void
     */
    public function setDate(?\DateTimeInterface $date): void
    {
        $this->date = $date;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     *
     * @return void
     */
    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }
}
