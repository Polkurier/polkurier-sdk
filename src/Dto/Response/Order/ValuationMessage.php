<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One carrier's price for an order, as quoted before placing it.
 *
 * The net and gross prices are the totals the order would be charged, with the promotion and the
 * rebate already taken off. The promotion and rebate amounts only say how much that saved.
 *
 * A price means something only when {@see self::$shipment} is true: a carrier priced on its own
 * that can't carry the shipment is quoted at 0. A conditional price of 0 likewise means no
 * conditional offer applies, not that the order is free.
 *
 * @api
 */
final readonly class ValuationMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_SERVICE_CODE = 'servicecode';
    public const string FIELD_SERVICE_NAME = 'serviceName';
    public const string FIELD_NET_PRICE = 'netprice';
    public const string FIELD_GROSS_PRICE = 'grossprice';
    public const string FIELD_CONDITIONAL_NET_PRICE = 'conditional_price_nett';
    public const string FIELD_CONDITIONAL_GROSS_PRICE = 'conditional_price_gross';
    public const string FIELD_PROMOTION_NET = 'promotion_nett';
    public const string FIELD_PROMOTION_GROSS = 'promotion_gross';
    public const string FIELD_REBATE_NET = 'rebate_nett';
    public const string FIELD_REBATE_GROSS = 'rebate_gross';
    public const string FIELD_SHIPMENT = 'shipment';
    public const string FIELD_AVAILABLE = 'available';
    public const string FIELD_UNAVAILABLE_MESSAGE = 'unavailable_message';
    /**-@#*/

    /**
     * @param string $serviceCode carrier code, the one createOrder() takes as the courier
     * @param string $serviceName carrier name for display
     * @param float $netPrice total net price in PLN, after the promotion and the rebate
     * @param float $grossPrice total gross price in PLN, after the promotion and the rebate
     * @param float $conditionalNetPrice a lower net total offered on a condition, 0 when no such
     *        offer applies
     * @param float $conditionalGrossPrice the same offer, gross
     * @param float $promotionNet promotion already included in the net price
     * @param float $promotionGross promotion already included in the gross price
     * @param float $rebateNet rebate already taken off the net price
     * @param float $rebateGross rebate already taken off the gross price
     * @param bool $shipment whether the carrier can carry this shipment. When false, the prices
     *        are 0 and mean nothing
     * @param bool $available whether the account can use the carrier for this order
     * @param string $unavailableMessage why the account can't use the carrier, possibly with HTML
     *        markup, empty otherwise
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_SERVICE_CODE)]
        public string $serviceCode = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_SERVICE_NAME)]
        public string $serviceName = '',

        /**
         * @var float total net price in PLN, after the promotion and the rebate
         */
        #[Serializer\SerializedName(self::FIELD_NET_PRICE)]
        public float $netPrice = 0.0,

        /**
         * @var float total gross price in PLN, after the promotion and the rebate
         */
        #[Serializer\SerializedName(self::FIELD_GROSS_PRICE)]
        public float $grossPrice = 0.0,

        /**
         * @var float a lower net total offered on a condition, 0 when no such offer applies
         */
        #[Serializer\SerializedName(self::FIELD_CONDITIONAL_NET_PRICE)]
        public float $conditionalNetPrice = 0.0,

        /**
         * @var float a lower gross total offered on a condition, 0 when no such offer applies
         */
        #[Serializer\SerializedName(self::FIELD_CONDITIONAL_GROSS_PRICE)]
        public float $conditionalGrossPrice = 0.0,

        /**
         * @var float promotion already included in the net price
         */
        #[Serializer\SerializedName(self::FIELD_PROMOTION_NET)]
        public float $promotionNet = 0.0,

        /**
         * @var float promotion already included in the gross price
         */
        #[Serializer\SerializedName(self::FIELD_PROMOTION_GROSS)]
        public float $promotionGross = 0.0,

        /**
         * @var float rebate already taken off the net price
         */
        #[Serializer\SerializedName(self::FIELD_REBATE_NET)]
        public float $rebateNet = 0.0,

        /**
         * @var float rebate already taken off the gross price
         */
        #[Serializer\SerializedName(self::FIELD_REBATE_GROSS)]
        public float $rebateGross = 0.0,

        /**
         * @var bool whether the carrier can carry this shipment. When false, the prices are 0
         */
        #[Serializer\SerializedName(self::FIELD_SHIPMENT)]
        public bool $shipment = false,

        /**
         * @var bool whether the account can use the carrier for this order
         */
        #[Serializer\SerializedName(self::FIELD_AVAILABLE)]
        public bool $available = false,

        /**
         * @var string why the account can't use the carrier, possibly with HTML markup, empty otherwise
         */
        #[Serializer\SerializedName(self::FIELD_UNAVAILABLE_MESSAGE)]
        public string $unavailableMessage = '',
    ) {}
}
