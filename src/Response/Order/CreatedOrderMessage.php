<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Response\KeepsRawResponseTrait;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The order as `create_order` reports it back.
 *
 * This is a short receipt, not the full {@see \Polkurier\Webservice\Response\Order} record
 * that `get_orders` returns. To read the order back in full, call `get_orders` with the returned
 * order number.
 */
readonly class CreatedOrderMessage implements MessageInterface, ParsedResponseInterface
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
        private string $orderNumber = '',

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_CONSIGN_ID)]
        #[OmitWhenEmpty]
        private ?string $consignId = null,

        /**
         * @var string[]
         */
        #[Serializer\SerializedName(self::FIELD_LABEL)]
        private array $label = [],

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_PRICE_NET)]
        private float $priceNet = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_PRICE_GROSS)]
        private float $priceGross = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_UNPAID_AMOUNT)]
        private float $unpaidAmount = 0.0,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_PAID)]
        private bool $paid = false,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_TRACKING_URI)]
        #[OmitWhenEmpty]
        private ?string $trackingUri = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_SHIPMENT_CODE)]
        #[OmitWhenEmpty]
        private ?string $shipmentCode = null,

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}

    /**
     * @return string
     */
    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    /**
     * @return string|null
     */
    public function getConsignId(): ?string
    {
        return $this->consignId;
    }

    /**
     * @return string[]
     */
    public function getLabel(): array
    {
        return $this->label;
    }

    /**
     * @return float
     */
    public function getPriceNet(): float
    {
        return $this->priceNet;
    }

    /**
     * @return float
     */
    public function getPriceGross(): float
    {
        return $this->priceGross;
    }

    /**
     * @return float
     */
    public function getUnpaidAmount(): float
    {
        return $this->unpaidAmount;
    }

    /**
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->paid;
    }

    /**
     * @return string|null
     */
    public function getTrackingUri(): ?string
    {
        return $this->trackingUri;
    }

    /**
     * @return string|null
     */
    public function getShipmentCode(): ?string
    {
        return $this->shipmentCode;
    }
}
