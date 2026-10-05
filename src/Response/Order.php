<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Response\Order\Cod;
use Polkurier\Webservice\Response\Order\IndividualPricing;
use Polkurier\Webservice\Response\Order\Item;
use Polkurier\Webservice\Response\Order\Pack;
use Polkurier\Webservice\Response\Order\Pickup;
use Polkurier\Webservice\Response\Order\Waybill;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

readonly class Order implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_NUMBER = 'number';
    public const string FIELD_STATUS = 'status';
    public const string FIELD_STATUS_CODE = 'status_code';
    public const string FIELD_DATE = 'date';
    public const string FIELD_STATUS_DATE = 'status_date';
    public const string FIELD_SHIPMENT_TYPE = 'shipmenttype';
    public const string FIELD_COURIER = 'courier';
    public const string FIELD_DESCRIPTION = 'description';
    public const string FIELD_SENDER = 'sender';
    public const string FIELD_RECIPIENT = 'recipient';
    public const string FIELD_COVER_ADDRESS = 'cover_address';
    public const string FIELD_WAYBILLS = 'waybills';
    public const string FIELD_CASH_ON_DELIVERY = 'COD';
    public const string FIELD_ITEMS = 'items';
    public const string FIELD_PACKS = 'packs';
    public const string FIELD_PRICE_NET = 'price_net';
    public const string FIELD_PRICE_GROSS = 'price_gross';
    public const string FIELD_UNPAID_AMOUNT = 'unpaid_amount';
    public const string FIELD_PAID = 'is_paid';
    public const string FIELD_INVOICE = 'has_invoice';
    public const string FIELD_INSURANCE_AMOUNT = 'insurance';
    public const string FIELD_COURIER_SERVICE = 'courierservice';
    public const string FIELD_PICKUP = 'pickup';
    public const string FIELD_TRACKING_URI = 'url';
    public const string FIELD_DELIVERED_DATE = 'delivered_date';
    public const string FIELD_ADDITIONAL_FIELDS = 'additional_fields';
    public const string FIELD_COURIER_NAME = 'courier_name';
    public const string FIELD_REBATE_NET = 'rebate_net';
    public const string FIELD_REBATE_GROSS = 'rebate_gross';
    public const string FIELD_VAT_RATE = 'vat_rate';
    public const string FIELD_LABEL = 'label';
    public const string FIELD_EXTERNAL_APPLICATION_ID = 'external_application_id';
    public const string FIELD_EXTERNAL_ORDER_ID = 'external_order_id';
    public const string FIELD_EXTERNAL_ORDER_NUMBER = 'external_order_number';
    public const string FIELD_SHIPMENT_CODE = 'shipment_code';
    public const string FIELD_INDIVIDUAL_PRICING = 'individual_pricing';
    public const string FIELD_FILES = 'files';
    /**-@#*/

    /**
     * @param string $status
     * @param string $statusCode
     * @param \DateTimeInterface $date
     * @param \DateTimeInterface $statusDate
     * @param ShipmentTypeEnum|null $shipmentType
     * @param string $courier
     * @param BaseAddress|null $sender
     * @param BaseAddress|null $recipient
     * @param Waybill[] $waybills
     * @param Item[] $items absent from the order list unless it was asked for them
     * @param Pack[] $packs absent from the order list unless it was asked for them
     * @param float $priceNet
     * @param float $priceGross
     * @param float $unpaidAmount
     * @param Pickup|null $pickup null when no collection has been arranged
     * @param string|null $number
     * @param string|null $description
     * @param Cod|null $cod
     * @param bool $paid
     * @param bool $invoice
     * @param float|null $insuranceAmount
     * @param string[]|null $courierService
     * @param string|null $trackingUri
     * @param \DateTimeInterface|null $deliveredDate
     * @param array<string, string>|null $additionalFields
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_STATUS)]
        private string $status,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_STATUS_CODE)]
        private string $statusCode,

        /**
         * @var \DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_DATE)]
        private \DateTimeInterface $date,

        /**
         * @var \DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_STATUS_DATE)]
        private \DateTimeInterface $statusDate,

        /**
         * @var ShipmentTypeEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_SHIPMENT_TYPE)]
        private ?ShipmentTypeEnum $shipmentType,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_COURIER)]
        private string $courier,

        /**
         * @var BaseAddress|null
         */
        #[Serializer\SerializedName(self::FIELD_SENDER)]
        private ?BaseAddress $sender,

        /**
         * @var BaseAddress|null
         */
        #[Serializer\SerializedName(self::FIELD_RECIPIENT)]
        private ?BaseAddress $recipient,

        /**
         * @var Waybill[]
         */
        #[Serializer\SerializedName(self::FIELD_WAYBILLS)]
        private array $waybills,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_PRICE_NET)]
        private float $priceNet,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_PRICE_GROSS)]
        private float $priceGross,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_UNPAID_AMOUNT)]
        private float $unpaidAmount,

        /**
         * @var Item[]
         */
        #[Serializer\SerializedName(self::FIELD_ITEMS)]
        private array $items = [],

        /**
         * @var Pack[]
         */
        #[Serializer\SerializedName(self::FIELD_PACKS)]
        private array $packs = [],

        /**
         * @var Pickup|null
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP)]
        private ?Pickup $pickup = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_NUMBER)]
        #[OmitWhenEmpty]
        private ?string $number = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        #[OmitWhenEmpty]
        private ?string $description = null,

        /**
         * @var BaseAddress|null
         */
        #[Serializer\SerializedName(self::FIELD_COVER_ADDRESS)]
        #[OmitWhenEmpty]
        private ?BaseAddress $coverAddress = null,

        /**
         * @var Cod|null
         */
        #[Serializer\SerializedName(self::FIELD_CASH_ON_DELIVERY)]
        #[OmitWhenEmpty]
        private ?Cod $cod = null,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_PAID)]
        private bool $paid = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_INVOICE)]
        private bool $invoice = false,

        /**
         * @var float|null
         */
        #[Serializer\SerializedName(self::FIELD_INSURANCE_AMOUNT)]
        #[OmitWhenEmpty]
        private ?float $insuranceAmount = null,

        /**
         * @var string[]|null
         */
        #[Serializer\SerializedName(self::FIELD_COURIER_SERVICE)]
        #[OmitWhenEmpty]
        private ?array $courierService = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_TRACKING_URI)]
        #[OmitWhenEmpty]
        private ?string $trackingUri = null,

        /**
         * @var \DateTimeInterface|null
         */
        #[Serializer\SerializedName(self::FIELD_DELIVERED_DATE)]
        #[OmitWhenEmpty]
        private ?\DateTimeInterface $deliveredDate = null,

        /**
         * @var array<string, string>|null
         */
        #[Serializer\SerializedName(self::FIELD_ADDITIONAL_FIELDS)]
        #[OmitWhenEmpty]
        private ?array $additionalFields = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_COURIER_NAME)]
        #[OmitWhenEmpty]
        private ?string $courierName = null,

        /**
         * @var float|null
         */
        #[Serializer\SerializedName(self::FIELD_REBATE_NET)]
        #[OmitWhenEmpty]
        private ?float $rebateNet = null,

        /**
         * @var float|null
         */
        #[Serializer\SerializedName(self::FIELD_REBATE_GROSS)]
        #[OmitWhenEmpty]
        private ?float $rebateGross = null,

        /**
         * @var float|null
         */
        #[Serializer\SerializedName(self::FIELD_VAT_RATE)]
        #[OmitWhenEmpty]
        private ?float $vatRate = null,

        /**
         * @var string[]|null
         */
        #[Serializer\SerializedName(self::FIELD_LABEL)]
        #[OmitWhenEmpty]
        private ?array $label = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_EXTERNAL_APPLICATION_ID)]
        #[OmitWhenEmpty]
        private ?string $externalApplicationId = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_EXTERNAL_ORDER_ID)]
        #[OmitWhenEmpty]
        private ?string $externalOrderId = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_EXTERNAL_ORDER_NUMBER)]
        #[OmitWhenEmpty]
        private ?string $externalOrderNumber = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_SHIPMENT_CODE)]
        #[OmitWhenEmpty]
        private ?string $shipmentCode = null,

        /**
         * @var IndividualPricing|null
         */
        #[Serializer\SerializedName(self::FIELD_INDIVIDUAL_PRICING)]
        #[OmitWhenEmpty]
        private ?IndividualPricing $individualPricing = null,

        /**
         * @var array<int, array<string, mixed>>|null
         */
        #[Serializer\SerializedName(self::FIELD_FILES)]
        #[OmitWhenEmpty]
        private ?array $files = null,
    ) {}

    /**
     * @return string|null
     */
    public function getNumber(): ?string
    {
        return $this->number;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getStatusCode(): string
    {
        return $this->statusCode;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getStatusDate(): \DateTimeInterface
    {
        return $this->statusDate;
    }

    /**
     * @return ShipmentTypeEnum|null null for a value this version of the SDK doesn't know
     */
    public function getShipmentType(): ?ShipmentTypeEnum
    {
        return $this->shipmentType;
    }

    /**
     * @return string
     */
    public function getCourier(): string
    {
        return $this->courier;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return BaseAddress|null null if the order came back without one
     */
    public function getSender(): ?BaseAddress
    {
        return $this->sender;
    }

    /**
     * @return BaseAddress|null null if the order came back without one
     */
    public function getRecipient(): ?BaseAddress
    {
        return $this->recipient;
    }

    /**
     * @return BaseAddress|null
     */
    public function getCoverAddress(): ?BaseAddress
    {
        return $this->coverAddress;
    }

    /**
     * @return Waybill[]
     */
    public function getWaybills(): array
    {
        return $this->waybills;
    }

    /**
     * @return Cod|null
     */
    public function getCod(): ?Cod
    {
        return $this->cod;
    }

    /**
     * @return Item[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return Pack[]
     */
    public function getPacks(): array
    {
        return $this->packs;
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
     * @return bool
     */
    public function hasInvoice(): bool
    {
        return $this->invoice;
    }

    /**
     * @return float|null
     */
    public function getInsuranceAmount(): ?float
    {
        return $this->insuranceAmount;
    }

    /**
     * Human-readable names of the additional services bought for this order.
     *
     * @return string[]|null
     */
    public function getCourierService(): ?array
    {
        return $this->courierService;
    }

    /**
     * When the courier is due to collect, or null when no collection has been arranged.
     *
     * @return Pickup|null
     */
    public function getPickup(): ?Pickup
    {
        return $this->pickup;
    }

    /**
     * @return string|null
     */
    public function getTrackingUri(): ?string
    {
        return $this->trackingUri;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDeliveredDate(): ?\DateTimeInterface
    {
        return $this->deliveredDate;
    }

    /**
     * Carrier-specific extra fields, keyed by field name. Not returned by getOrders().
     *
     * @return array<string, scalar>|null
     */
    public function getAdditionalFields(): ?array
    {
        return $this->additionalFields;
    }

    /**
     * Display name of the carrier, e.g. "UPS Standard". getCourier() returns the API's carrier code
     * instead.
     *
     * @return string|null
     */
    public function getCourierName(): ?string
    {
        return $this->courierName;
    }

    /**
     * Net value of the discount applied to this order, in PLN.
     *
     * @return float|null
     */
    public function getRebateNet(): ?float
    {
        return $this->rebateNet;
    }

    /**
     * Gross value of the discount applied to this order, in PLN.
     *
     * @return float|null
     */
    public function getRebateGross(): ?float
    {
        return $this->rebateGross;
    }

    /**
     * VAT rate applied to the order, as a percentage (23.0 for 23%).
     *
     * @return float|null
     */
    public function getVatRate(): ?float
    {
        return $this->vatRate;
    }

    /**
     * Waybill numbers as a flat list. getWaybills() returns the same numbers as objects and also
     * marks the primary waybill.
     *
     * @return string[]|null
     */
    public function getLabel(): ?array
    {
        return $this->label;
    }

    /**
     * Identifier of the external application (integration) that created the order, if any.
     *
     * @return string|null
     */
    public function getExternalApplicationId(): ?string
    {
        return $this->externalApplicationId;
    }

    /**
     * The integration's own identifier for this order, as supplied when it was created.
     *
     * @return string|null
     */
    public function getExternalOrderId(): ?string
    {
        return $this->externalOrderId;
    }

    /**
     * The integration's own human-readable order number, as supplied when it was created.
     *
     * @return string|null
     */
    public function getExternalOrderNumber(): ?string
    {
        return $this->externalOrderNumber;
    }

    /**
     * Code the sender presents at the drop-off point. Present only for orders using the
     * label-less shipping service.
     *
     * @return string|null
     */
    public function getShipmentCode(): ?string
    {
        return $this->shipmentCode;
    }

    /**
     * Individually negotiated pricing. Present only for freight (spedycja) orders.
     *
     * @return IndividualPricing|null
     */
    public function getIndividualPricing(): ?IndividualPricing
    {
        return $this->individualPricing;
    }

    /**
     * Documents attached to the order. Returned only when the request asked for files.
     *
     * @return array<int, array<string, mixed>>|null
     */
    public function getFiles(): ?array
    {
        return $this->files;
    }
}
