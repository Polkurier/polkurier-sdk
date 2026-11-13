<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\MoneyBox;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * What a payout would cost, quoted before it is requested.
 *
 * Nothing is reserved and no payout is created. The fee can combine several components, and the
 * breakdown itemises them.
 *
 * While the account has free payouts left this month, the fee has no PAYOUT_FEE component.
 */
final readonly class MoneyBoxFeeMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_FEE = 'fee';
    public const string FIELD_FEE_NETT = 'fee_nett';
    public const string FIELD_FEE_VAT_RATE = 'fee_vat_rate';
    public const string FIELD_FREE_PAYOUTS_LEFT = 'free_payouts_left';
    public const string FIELD_FREE_PAYOUTS_TOTAL = 'free_payouts_total';
    public const string FIELD_FEES = 'fees';
    /**-@#*/

    /**
     * @param float $fee total gross fee in PLN
     * @param float $feeNett total net fee in PLN
     * @param float $feeVatRate VAT rate as a percentage
     * @param int $freePayoutsLeft free payouts still available this month
     * @param int $freePayoutsTotal free payouts granted per month
     * @param MoneyBoxFeeComponentMessage[] $fees itemised components of the total
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_FEE)]
        private float $fee = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_FEE_NETT)]
        private float $feeNett = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_FEE_VAT_RATE)]
        private float $feeVatRate = 0.0,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_FREE_PAYOUTS_LEFT)]
        private int $freePayoutsLeft = 0,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_FREE_PAYOUTS_TOTAL)]
        private int $freePayoutsTotal = 0,

        /**
         * @var MoneyBoxFeeComponentMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_FEES)]
        private array $fees = [],

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}

    /**
     * @return float
     */
    public function getFee(): float
    {
        return $this->fee;
    }

    /**
     * @return float
     */
    public function getFeeNett(): float
    {
        return $this->feeNett;
    }

    /**
     * @return float
     */
    public function getFeeVatRate(): float
    {
        return $this->feeVatRate;
    }

    /**
     * @return int
     */
    public function getFreePayoutsLeft(): int
    {
        return $this->freePayoutsLeft;
    }

    /**
     * @return int
     */
    public function getFreePayoutsTotal(): int
    {
        return $this->freePayoutsTotal;
    }

    /**
     * @return MoneyBoxFeeComponentMessage[]
     */
    public function getFees(): array
    {
        return $this->fees;
    }
}
