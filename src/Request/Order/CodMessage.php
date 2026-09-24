<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Order\CodReturnTypeEnum;
use Polkurier\Webservice\Enum\Order\CodTypeEnum;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Cash on delivery, as it is requested on an order.
 *
 * The amount is what the courier collects from the recipient, in PLN. At zero there is no
 * collection and the rest of the block does not apply. The settlement type decides how quickly
 * the money is passed on, and the return type where it goes — to a bank account, which then needs
 * one supplying, or into the account's money box.
 *
 * What became of the money afterwards is reported on the order, not here; see
 * {@see \Polkurier\Webservice\Response\Order\Cod}.
 */
#[Validate\IgnoredWhen(property: 'amount', equals: 0.0)]
#[Validate\RequiredWhen(
    field: 'bankAccount',
    property: 'returnType',
    equals: CodReturnTypeEnum::BankAccount,
    because: 'is where the collected money is to be sent',
)]
readonly class CodMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TYPE = 'codtype';
    public const string FIELD_AMOUNT = 'codamount';
    public const string FIELD_BANK_ACCOUNT = 'codbankaccount';
    public const string FIELD_RETURN_TYPE = 'return_cod';
    /**-@#*/

    /**
     * @param float $amount amount to collect, in PLN; zero means no collection and the rest of the
     *                       block does not apply
     * @param string $bankAccount destination account; required when returning to a bank account
     * @param CodTypeEnum $type how quickly the collected money is settled
     * @param CodReturnTypeEnum $returnType where it is sent
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
        #[Validate\Iban]
        private string $bankAccount = '',

        /**
         * @var CodTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        private CodTypeEnum $type = CodTypeEnum::Standard,

        /**
         * @var CodReturnTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_RETURN_TYPE)]
        private CodReturnTypeEnum $returnType = CodReturnTypeEnum::BankAccount,
    ) {}

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
     * @return CodTypeEnum
     */
    public function getType(): CodTypeEnum
    {
        return $this->type;
    }

    /**
     * @return CodReturnTypeEnum
     */
    public function getReturnType(): CodReturnTypeEnum
    {
        return $this->returnType;
    }
}
