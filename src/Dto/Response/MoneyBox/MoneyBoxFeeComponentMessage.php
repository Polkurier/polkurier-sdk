<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\MoneyBox;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\MoneyBox\MoneyBoxFeeComponentTypeEnum;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One line of the payout fee breakdown.
 *
 * @api
 */
final readonly class MoneyBoxFeeComponentMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TYPE = 'type';
    public const string FIELD_NAME = 'name';
    public const string FIELD_DESCRIPTION = 'description';
    public const string FIELD_FEE = 'fee';
    public const string FIELD_FEE_NET = 'fee_nett';
    public const string FIELD_VAT_RATE = 'vat_rate';
    /**-@#*/

    /**
     * @param MoneyBoxFeeComponentTypeEnum|null $type what the component is charged for
     * @param string $name display name, in Polish
     * @param string $description explanation, in Polish, possibly empty
     * @param float $fee gross amount in PLN
     * @param float $feeNet net amount in PLN
     * @param float $vatRate VAT rate as a percentage
     */
    public function __construct(
        /**
         * @var MoneyBoxFeeComponentTypeEnum|null null for a value the SDK doesn't know
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        public ?MoneyBoxFeeComponentTypeEnum $type = null,

        #[Serializer\SerializedName(self::FIELD_NAME)]
        public string $name = '',

        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        public string $description = '',

        #[Serializer\SerializedName(self::FIELD_FEE)]
        public float $fee = 0.0,

        #[Serializer\SerializedName(self::FIELD_FEE_NET)]
        public float $feeNet = 0.0,

        #[Serializer\SerializedName(self::FIELD_VAT_RATE)]
        public float $vatRate = 0.0,
    ) {}
}
