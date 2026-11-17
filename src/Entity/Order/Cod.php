<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Entity\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Dto\Response\Order\Cod\ReturnDetails;
use Polkurier\Webservice\Enum\Order\CodReturnTypeEnum;
use Polkurier\Webservice\Enum\Order\CodStatusEnum;
use Polkurier\Webservice\Enum\Order\CodTypeEnum;
use Polkurier\Webservice\Log\Attribute\PersonalData;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Serializer\Attribute\ResponseOnly;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The cash-on-delivery block of an order.
 *
 * The amount is what the courier collects from the recipient, in PLN. Zero means no collection,
 * and the other fields are then not needed. The settlement type sets how quickly the money is paid
 * out. The return type sets where it goes: to a bank account (then `$bankAccount` is required), by
 * postal order, or to the account's money box.
 *
 * `status` and `returnDetails` report what happened to the money and are not sent.
 *
 * @api
 */
#[Validate\IgnoredWhen(property: 'amount', equals: 0.0)]
#[Validate\RequiredWhen(
    field: 'bankAccount',
    property: 'returnType',
    equals: CodReturnTypeEnum::BankAccount,
    reason: 'is where the collected money is to be sent',
)]
class Cod implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TYPE = 'codtype';
    public const string FIELD_AMOUNT = 'codamount';
    public const string FIELD_BANK_ACCOUNT = 'codbankaccount';
    public const string FIELD_RETURN_TYPE = 'return_cod';
    public const string FIELD_STATUS = 'status';
    public const string FIELD_RETURN_DETAILS = 'return_details';
    /**-@#*/

    /**
     * @param float $amount amount to collect, in PLN. Zero means no collection, and the other
     *                       fields are then not needed
     * @param string $bankAccount destination account, required when the return type is a bank
     *        account. Polish accounts only
     * @param CodTypeEnum|null $type how quickly the collected money is settled
     * @param CodReturnTypeEnum|null $returnType where it is sent
     * @param CodStatusEnum|null $status where the money has got to
     * @param ReturnDetails[] $returnDetails one entry per movement of the money
     */
    public function __construct(
        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_AMOUNT)]
        #[Validate\NonNegative]
        private float $amount,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_BANK_ACCOUNT)]
        #[Validate\Iban(foreign: false)]
        #[OmitWhenEmpty]
        #[PersonalData]
        private string $bankAccount = '',

        /**
         * @var CodTypeEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        #[Validate\NotBlank]
        private ?CodTypeEnum $type = CodTypeEnum::Standard,

        /**
         * @var CodReturnTypeEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_RETURN_TYPE)]
        #[Validate\NotBlank]
        private ?CodReturnTypeEnum $returnType = CodReturnTypeEnum::BankAccount,

        /**
         * @var CodStatusEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_STATUS)]
        #[ResponseOnly]
        private ?CodStatusEnum $status = null,

        /**
         * @var ReturnDetails[]
         */
        #[Serializer\SerializedName(self::FIELD_RETURN_DETAILS)]
        #[ResponseOnly]
        private array $returnDetails = [],
    ) {}

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
     * @return static
     */
    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return string
     */
    public function getBankAccount(): string
    {
        return $this->bankAccount;
    }

    /**
     * @param string $bankAccount
     *
     * @return static
     */
    public function setBankAccount(string $bankAccount): static
    {
        $this->bankAccount = $bankAccount;

        return $this;
    }

    /**
     * @return CodTypeEnum|null
     */
    public function getType(): ?CodTypeEnum
    {
        return $this->type;
    }

    /**
     * @param CodTypeEnum|null $type
     *
     * @return static
     */
    public function setType(?CodTypeEnum $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return CodReturnTypeEnum|null
     */
    public function getReturnType(): ?CodReturnTypeEnum
    {
        return $this->returnType;
    }

    /**
     * @param CodReturnTypeEnum|null $returnType
     *
     * @return static
     */
    public function setReturnType(?CodReturnTypeEnum $returnType): static
    {
        $this->returnType = $returnType;

        return $this;
    }

    /**
     * Where the collected money has got to.
     *
     * @return CodStatusEnum|null null for a value the SDK doesn't know
     */
    public function getStatus(): ?CodStatusEnum
    {
        return $this->status;
    }

    /**
     * @return ReturnDetails[]
     */
    public function getReturnDetails(): array
    {
        return $this->returnDetails;
    }
}
