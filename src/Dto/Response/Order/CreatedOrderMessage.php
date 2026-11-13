<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The order as `create_order` reports it back.
 *
 * This is a short receipt, not the full {@see \Polkurier\Webservice\Entity\Order\Order} record
 * that `get_orders` returns. To read the order back in full, call `get_orders` with the returned
 * order number.
 */
final readonly class CreatedOrderMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ORDER_NUMBER = 'order_number';
    public const string FIELD_CONSIGN_ID = 'consignid';
    public const string FIELD_LABEL = 'label';
    public const string FIELD_PRICE_NET = 'price_net';
    public const string FIELD_PRICE_GROSS = 'price_gross';
    public const string FIELD_UNPAID_AMOUNT = 'unpaid_amount';
    public const string FIELD_PAID = 'is_paid';
    public const string FIELD_TRACKING_URI = 'url_tracktrace';
    public const string FIELD_SHIPMENT_CODE = 'shipment_code';
    /**-@#*/

    /**
     * @param string $orderNumber Polkurier's own order number
     * @param string|null $consignId carrier-side consignment identifier, GLS_FOREIGN and
     *        GLS_DOMESTIC orders only
     * @param string[] $label waybill numbers assigned to the order
     * @param float $priceNet
     * @param float $priceGross
     * @param float $unpaidAmount amount still to be settled, in PLN
     * @param bool $paid
     * @param string|null $trackingUri public track-and-trace URL
     * @param string|null $shipmentCode drop-off code, label-less service only
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ORDER_NUMBER)]
        public string $orderNumber = '',

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_CONSIGN_ID)]
        #[OmitWhenEmpty]
        public ?string $consignId = null,

        /**
         * @var string[]
         */
        #[Serializer\SerializedName(self::FIELD_LABEL)]
        public array $label = [],

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_PRICE_NET)]
        public float $priceNet = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_PRICE_GROSS)]
        public float $priceGross = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_UNPAID_AMOUNT)]
        public float $unpaidAmount = 0.0,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_PAID)]
        public bool $paid = false,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_TRACKING_URI)]
        #[OmitWhenEmpty]
        public ?string $trackingUri = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_SHIPMENT_CODE)]
        #[OmitWhenEmpty]
        public ?string $shipmentCode = null,

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}
}
