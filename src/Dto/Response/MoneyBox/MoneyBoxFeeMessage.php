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
 *
 * @api
 */
final readonly class MoneyBoxFeeMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_FEE = 'fee';
    public const string FIELD_FEE_NET = 'fee_nett';
    public const string FIELD_FEE_VAT_RATE = 'fee_vat_rate';
    public const string FIELD_FREE_PAYOUTS_LEFT = 'free_payouts_left';
    public const string FIELD_FREE_PAYOUTS_TOTAL = 'free_payouts_total';
    public const string FIELD_FEES = 'fees';
    /**-@#*/

    /**
     * @param float $fee total gross fee in PLN
     * @param float $feeNet total net fee in PLN
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
        public float $fee = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_FEE_NET)]
        public float $feeNet = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_FEE_VAT_RATE)]
        public float $feeVatRate = 0.0,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_FREE_PAYOUTS_LEFT)]
        public int $freePayoutsLeft = 0,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_FREE_PAYOUTS_TOTAL)]
        public int $freePayoutsTotal = 0,

        /**
         * @var MoneyBoxFeeComponentMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_FEES)]
        public array $fees = [],

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}
}
