<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\MoneyBox;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * What a payout would cost, quoted before it is requested.
 *
 * Purely a quote — nothing is reserved and no payout is created. The breakdown itemises the total,
 * which matters because the fee can combine several components.
 *
 * The free payout counters are the reason to call this even when the fee looks predictable: an
 * account with free payouts remaining is charged nothing.
 */
readonly class MoneyBoxFeeMessage implements MessageInterface
{
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
     * @param int $freePayoutsLeft free payouts still available this period
     * @param int $freePayoutsTotal free payouts granted per period
     * @param MoneyBoxFeeComponentMessage[] $fees itemised components of the total
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
