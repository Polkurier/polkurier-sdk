<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\MoneyBox;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One line of the payout fee breakdown.
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
     * @param string $type machine-readable component code: PAYOUT_FEE or POSTAL_TRANSFER_FEE
     * @param string $name display name, in Polish
     * @param string $description explanation, in Polish, possibly empty
     * @param float $fee gross amount in PLN
     * @param float $feeNet net amount in PLN
     * @param float $vatRate VAT rate as a percentage
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        public string $type = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        public string $name = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        public string $description = '',

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
        #[Serializer\SerializedName(self::FIELD_VAT_RATE)]
        public float $vatRate = 0.0,
    ) {}
}
