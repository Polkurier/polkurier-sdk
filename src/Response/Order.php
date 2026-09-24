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

class Order implements MessageInterface
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
     * @param ShipmentTypeEnum $shipmentType
     * @param string $courier
     * @param BaseAddress $sender
     * @param BaseAddress $recipient
     * @param iterable<Waybill> $waybills
     * @param iterable<Item> $items absent from the order list; get_orders does not carry them
     * @param iterable<Pack> $packs absent from the order list unless it was asked for them
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
         * @var ShipmentTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_SHIPMENT_TYPE)]
        private ShipmentTypeEnum $shipmentType,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_COURIER)]
        private string $courier,

        /**
         * @var BaseAddress
         */
        #[Serializer\SerializedName(self::FIELD_SENDER)]
        private BaseAddress $sender,

        /**
         * @var BaseAddress
         */
        #[Serializer\SerializedName(self::FIELD_RECIPIENT)]
        private BaseAddress $recipient,

        /**
         * @var iterable<Waybill>
         */
        #[Serializer\SerializedName(self::FIELD_WAYBILLS)]
        private iterable $waybills,

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
         * @var iterable<Item>
         */
        #[Serializer\SerializedName(self::FIELD_ITEMS)]
        private iterable $items = [],

        /**
         * @var iterable<Pack>
         */
        #[Serializer\SerializedName(self::FIELD_PACKS)]
        private iterable $packs = [],

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
     * @param string|null $number
     *
     * @return void
     */
    public function setNumber(?string $number): void
    {
        $this->number = $number;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return void
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getStatusCode(): string
    {
        return $this->statusCode;
    }

    /**
     * @param string $statusCode
     *
     * @return void
     */
    public function setStatusCode(string $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    /**
     * @param \DateTimeInterface $date
     *
     * @return void
     */
    public function setDate(\DateTimeInterface $date): void
    {
        $this->date = $date;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getStatusDate(): \DateTimeInterface
    {
        return $this->statusDate;
    }

    /**
     * @param \DateTimeInterface $statusDate
     *
     * @return void
     */
    public function setStatusDate(\DateTimeInterface $statusDate): void
    {
        $this->statusDate = $statusDate;
    }

    /**
     * @return ShipmentTypeEnum
     */
    public function getShipmentType(): ShipmentTypeEnum
    {
        return $this->shipmentType;
    }

    /**
     * @param ShipmentTypeEnum $shipmentType
     *
     * @return void
     */
    public function setShipmentType(ShipmentTypeEnum $shipmentType): void
    {
        $this->shipmentType = $shipmentType;
    }

    /**
     * @return string
     */
    public function getCourier(): string
    {
        return $this->courier;
    }

    /**
     * @param string $courier
     *
     * @return void
     */
    public function setCourier(string $courier): void
    {
        $this->courier = $courier;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     *
     * @return void
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return BaseAddress
     */
    public function getSender(): BaseAddress
    {
        return $this->sender;
    }

    /**
     * @param BaseAddress $sender
     *
     * @return void
     */
    public function setSender(BaseAddress $sender): void
    {
        $this->sender = $sender;
    }

    /**
     * @return BaseAddress
     */
    public function getRecipient(): BaseAddress
    {
        return $this->recipient;
    }

    /**
     * @param BaseAddress $recipient
     *
     * @return void
     */
    public function setRecipient(BaseAddress $recipient): void
    {
        $this->recipient = $recipient;
    }

    /**
     * @return BaseAddress|null
     */
    public function getCoverAddress(): ?BaseAddress
    {
        return $this->coverAddress;
    }

    /**
     * @param BaseAddress|null $coverAddress
     *
     * @return void
     */
    public function setCoverAddress(?BaseAddress $coverAddress): void
    {
        $this->coverAddress = $coverAddress;
    }

    /**
     * @return iterable<Waybill>
     */
    public function getWaybills(): iterable
    {
        return $this->waybills;
    }

    /**
     * @param iterable<Waybill> $waybills
     *
     * @return void
     */
    public function setWaybills(iterable $waybills): void
    {
        $this->waybills = $waybills;
    }

    /**
     * @return Cod|null
     */
    public function getCod(): ?Cod
    {
        return $this->cod;
    }

    /**
     * @param Cod|null $cod
     *
     * @return void
     */
    public function setCod(?Cod $cod): void
    {
        $this->cod = $cod;
    }

    /**
     * @return iterable<Item>
     */
    public function getItems(): iterable
    {
        return $this->items;
    }

    /**
     * @param iterable<Item> $items absent from the order list; get_orders does not carry them
     *
     * @return void
     */
    public function setItems(iterable $items): void
    {
        $this->items = $items;
    }

    /**
     * @return iterable<Pack>
     */
    public function getPacks(): iterable
    {
        return $this->packs;
    }

    /**
     * @param iterable<Pack> $packs absent from the order list unless it was asked for them
     *
     * @return void
     */
    public function setPacks(iterable $packs): void
    {
        $this->packs = $packs;
    }

    /**
     * @return float
     */
    public function getPriceNet(): float
    {
        return $this->priceNet;
    }

    /**
     * @param float $priceNet
     *
     * @return void
     */
    public function setPriceNet(float $priceNet): void
    {
        $this->priceNet = $priceNet;
    }

    /**
     * @return float
     */
    public function getPriceGross(): float
    {
        return $this->priceGross;
    }

    /**
     * @param float $priceGross
     *
     * @return void
     */
    public function setPriceGross(float $priceGross): void
    {
        $this->priceGross = $priceGross;
    }

    /**
     * @return float
     */
    public function getUnpaidAmount(): float
    {
        return $this->unpaidAmount;
    }

    /**
     * @param float $unpaidAmount
     *
     * @return void
     */
    public function setUnpaidAmount(float $unpaidAmount): void
    {
        $this->unpaidAmount = $unpaidAmount;
    }

    /**
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->paid;
    }

    /**
     * @param bool $paid
     *
     * @return void
     */
    public function setPaid(bool $paid): void
    {
        $this->paid = $paid;
    }

    /**
     * @return bool
     */
    public function hasInvoice(): bool
    {
        return $this->invoice;
    }

    /**
     * @param bool $invoice
     *
     * @return void
     */
    public function setInvoice(bool $invoice): void
    {
        $this->invoice = $invoice;
    }

    /**
     * @return float|null
     */
    public function getInsuranceAmount(): ?float
    {
        return $this->insuranceAmount;
    }

    /**
     * @param float|null $insuranceAmount
     *
     * @return void
     */
    public function setInsuranceAmount(?float $insuranceAmount): void
    {
        $this->insuranceAmount = $insuranceAmount;
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
     * @param string[]|null $courierService
     *
     * @return void
     */
    public function setCourierService(?array $courierService): void
    {
        $this->courierService = $courierService;
    }

    /**
     * When the courier is due to collect, or null when no collection has been arranged — which
     * the API reports as an empty block rather than by leaving the field out.
     *
     * @return Pickup|null
     */
    public function getPickup(): ?Pickup
    {
        return $this->pickup;
    }

    /**
     * @param Pickup|null $pickup null when no collection has been arranged
     *
     * @return void
     */
    public function setPickup(?Pickup $pickup): void
    {
        $this->pickup = $pickup;
    }

    /**
     * @return string|null
     */
    public function getTrackingUri(): ?string
    {
        return $this->trackingUri;
    }

    /**
     * @param string|null $trackingUri
     *
     * @return void
     */
    public function setTrackingUri(?string $trackingUri): void
    {
        $this->trackingUri = $trackingUri;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDeliveredDate(): ?\DateTimeInterface
    {
        return $this->deliveredDate;
    }

    /**
     * @param \DateTimeInterface|null $deliveredDate
     *
     * @return void
     */
    public function setDeliveredDate(?\DateTimeInterface $deliveredDate): void
    {
        $this->deliveredDate = $deliveredDate;
    }

    /**
      * Carrier-specific extra fields, keyed by field name. Send-only: get_orders does not echo them.
      *
     * @return array<string, scalar>|null
     */
    public function getAdditionalFields(): ?array
    {
        return $this->additionalFields;
    }

    /**
     * @param array<string, scalar>|null $additionalFields
     *
     * @return void
     */
    public function setAdditionalFields(?array $additionalFields): void
    {
        $this->additionalFields = $additionalFields;
    }

    /**
     * Display name of the carrier, e.g. "UPS Standard". Distinct from getCourier(), which returns
     * the API's carrier code.
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
     * Waybill numbers as a flat list. The same numbers are available as objects through
     * getWaybills(), which additionally says which one is the primary waybill.
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
