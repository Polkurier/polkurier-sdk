<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Order\CodReturnTypeEnum;
use Polkurier\Webservice\Enum\Order\CodStatusEnum;
use Polkurier\Webservice\Enum\Order\CodTypeEnum;
use Polkurier\Webservice\Response\Order\Cod\ReturnDetails;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Cash on delivery: how much to collect from the recipient and where it goes.
 *
 * An amount of zero means no collection.
 */
readonly class Cod implements MessageInterface
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
     * Cash on delivery as reported on an order. To request it, use
     * {@see \Polkurier\Webservice\Request\Order\CodMessage}. No field here is nullable.
     *
     * @param float $amount amount collected from the recipient, in PLN
     * @param string $bankAccount the account the money is returned to
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
        private float $amount,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_BANK_ACCOUNT)]
        private string $bankAccount = '',

        /**
         * @var CodTypeEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        private ?CodTypeEnum $type = CodTypeEnum::Standard,

        /**
         * @var CodReturnTypeEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_RETURN_TYPE)]
        private ?CodReturnTypeEnum $returnType = CodReturnTypeEnum::BankAccount,

        /**
         * @var CodStatusEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_STATUS)]
        private ?CodStatusEnum $status = CodStatusEnum::Waiting,

        /**
         * @var ReturnDetails[]
         */
        #[Serializer\SerializedName(self::FIELD_RETURN_DETAILS)]
        private array $returnDetails = [],
    ) {}

    /**
     * @return CodTypeEnum|null null for a value this version of the SDK doesn't know
     */
    public function getType(): ?CodTypeEnum
    {
        return $this->type;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getBankAccount(): string
    {
        return $this->bankAccount;
    }

    /**
     * @return CodReturnTypeEnum|null null for a value this version of the SDK doesn't know
     */
    public function getReturnType(): ?CodReturnTypeEnum
    {
        return $this->returnType;
    }

    /**
     * Where the collected money has got to.
     *
     * @return CodStatusEnum|null null for a value this version of the SDK doesn't know
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
