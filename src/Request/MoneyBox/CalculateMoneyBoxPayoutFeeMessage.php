<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\MoneyBox;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\MoneyBox\MoneyBoxPayoutTypeEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Asks what a payout would cost, without requesting one.
 *
 * Send the orders and payout type you intend to use to get the fee that will apply. Leave out the
 * orders to get the fee for paying out everything available.
 */
readonly class CalculateMoneyBoxPayoutFeeMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ORDERS = 'orders';
    public const string FIELD_PAYOUT_TYPE = 'payout_type';
    /**-@#*/

    /**
     * @param string[] $orders order numbers to include, empty for everything available
     * @param ?MoneyBoxPayoutTypeEnum $payoutType the delivery method, defaults to the account's configured one
     */
    public function __construct(
        /**
         * @var string[]
         */
        #[Serializer\SerializedName(self::FIELD_ORDERS)]
        #[OmitWhenEmpty]
        #[Validate\Each(Validate\OrderNumber::class)]
        private array $orders = [],

        /**
         * @var ?MoneyBoxPayoutTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_PAYOUT_TYPE)]
        #[OmitWhenEmpty]
        private ?MoneyBoxPayoutTypeEnum $payoutType = null,
    ) {}

    /**
     * @return string[]
     */
    public function getOrders(): array
    {
        return $this->orders;
    }

    /**
     * @return ?MoneyBoxPayoutTypeEnum
     */
    public function getPayoutType(): ?MoneyBoxPayoutTypeEnum
    {
        return $this->payoutType;
    }
}
