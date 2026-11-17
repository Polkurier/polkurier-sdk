<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Entity;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Dto\Response\Order\File;
use Polkurier\Webservice\Dto\Response\Order\IndividualPricing;
use Polkurier\Webservice\Dto\Response\Order\Item;
use Polkurier\Webservice\Dto\Response\Order\Waybill;
use Polkurier\Webservice\Entity\Order\Address;
use Polkurier\Webservice\Entity\Order\Cod;
use Polkurier\Webservice\Entity\Order\PackItem;
use Polkurier\Webservice\Entity\Order\Pickup;
use Polkurier\Webservice\Enum\Order\AdditionalServiceEnum;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Serializer\Attribute\ResponseOnly;
use Polkurier\Webservice\Validator\Message\OrderValidator;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * An order: placed with {@see \Polkurier\Webservice\Dto\Request\Order\CreateOrderMessage}, read
 * from {@see \Polkurier\Webservice\Api\Service\OrdersServiceInterface::getOrders()} or a webhook
 * event.
 *
 * Parcels, items and files are filled in when the order list asks for them or is filtered by order
 * number. A webhook event carries parcels and items, not files.
 *
 * These requirements vary by carrier or account, so this class does not check them:
 *
 * - `pickup`: some carriers need a pickup date, some also a time window. `available_carriers`
 *   reports which, per carrier.
 * - {@see AdditionalServiceEnum::WeekendDelivery} is only available for InPost parcel lockers, and
 *   only inside a weekly ordering window.
 * - The recipient SMS notifications (on dispatch and on going out for delivery) are ordered
 *   separately. Each `_WITH_NAME` variant needs a default SMS sender name on the account and
 *   replaces its plain variant. {@see AdditionalServiceEnum} describes how the two kinds combine.
 * - `description` is cut to fit its length limit, {@see self::DESCRIPTION_MAX_LENGTH} bytes unless
 *   the account or the carrier has another.
 *
 * To find the order later by your own id, set `externalApplicationId` and `externalOrderId`
 * together (the SDK refuses one without the other), then filter
 * {@see \Polkurier\Webservice\Api\Service\OrdersServiceInterface::getOrders()} by both. This is
 * how to check whether an order was placed when its `create_order` call got no answer.
 *
 * @api
 */
#[Validate\ValidatedBy(OrderValidator::class)]
#[Validate\RequiredWith(field: 'externalApplicationId', with: 'externalOrderId')]
#[Validate\RequiredWith(field: 'externalOrderId', with: 'externalApplicationId')]
class Order implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_COURIER = 'courier';
    public const string FIELD_SHIPMENT_TYPE = 'shipmenttype';
    public const string FIELD_PACKS = 'packs';
    public const string FIELD_SENDER = 'sender';
    public const string FIELD_RECIPIENT = 'recipient';
    public const string FIELD_CASH_ON_DELIVERY = 'COD';
    public const string FIELD_INSURANCE = 'insurance';
    public const string FIELD_COVER_ADDRESS = 'cover_address';
    public const string FIELD_PICKUP = 'pickup';
    public const string FIELD_COURIER_SERVICE = 'courierservice';
    public const string FIELD_ADDITIONAL_FIELDS = 'additional_fields';
    public const string FIELD_DESCRIPTION = 'description';
    public const string FIELD_EXTERNAL_APPLICATION_ID = 'external_application_id';
    public const string FIELD_EXTERNAL_ORDER_ID = 'external_order_id';
    public const string FIELD_EXTERNAL_ORDER_NUMBER = 'external_order_number';
    public const string FIELD_NUMBER = 'number';
    public const string FIELD_STATUS = 'status';
    public const string FIELD_STATUS_CODE = 'status_code';
    public const string FIELD_DATE = 'date';
    public const string FIELD_STATUS_DATE = 'status_date';
    public const string FIELD_WAYBILLS = 'waybills';
    public const string FIELD_PRICE_NET = 'price_net';
    public const string FIELD_PRICE_GROSS = 'price_gross';
    public const string FIELD_UNPAID_AMOUNT = 'unpaid_amount';
    public const string FIELD_ITEMS = 'items';
    public const string FIELD_PAID = 'is_paid';
    public const string FIELD_INVOICE = 'has_invoice';
    /**
     * Not a wire field: the API answers with the service names under `courierservice`, the key
     * their codes are sent under, and the SDK reads them into {@see self::$courierServiceNames}.
     */
    public const string FIELD_COURIER_SERVICE_NAMES = 'courierservice_names';
    public const string FIELD_TRACKING_URI = 'url';
    public const string FIELD_DELIVERED_DATE = 'delivered_date';
    public const string FIELD_COURIER_NAME = 'courier_name';
    public const string FIELD_REBATE_NET = 'rebate_net';
    public const string FIELD_REBATE_GROSS = 'rebate_gross';
    public const string FIELD_VAT_RATE = 'vat_rate';
    public const string FIELD_LABEL = 'label';
    public const string FIELD_SHIPMENT_CODE = 'shipment_code';
    public const string FIELD_INDIVIDUAL_PRICING = 'individual_pricing';
    public const string FIELD_FILES = 'files';
    /**-@#*/

    /**
     * Length of `description` in bytes of UTF-8, beyond which the API cuts it, unless the account or
     * the carrier has another limit.
     */
    public const int DESCRIPTION_MAX_LENGTH = 30;

    /**
     * @param string $courier carrier code, as returned by `available_carriers`
     * @param ShipmentTypeEnum|null $shipmentType null when read back with a value the SDK doesn't know
     * @param PackItem[] $packs at least one
     * @param Address|null $sender
     * @param Address|null $recipient
     * @param Cod|null $cod cash on delivery. An amount of zero means no collection, and the other
     *        fields are then not needed
     * @param float|null $insurance declared value in PLN, zero for no insurance
     * @param Address|null $coverAddress alternative sender address to print on the label. It
     *        orders {@see AdditionalServiceEnum::CoverSenderAddress} as well
     * @param Pickup|null $pickup when the courier is due to collect, null when no collection has
     *        been arranged
     * @param AdditionalServiceEnum[] $courierService services to order, sent as the flag map the
     *        API expects. Not read back, see $courierServiceNames
     * @param array<string, scalar>|null $additionalFields carrier-specific extra fields, keyed by
     *        field name
     * @param string|null $description what the parcels hold. Required by `create_order`, not for
     *        pricing
     * @param string|null $externalApplicationId UUID of your application. Set it together with
     *        $externalOrderId to link the order to your own record
     * @param string|null $externalOrderId your own id for the order. Needs $externalApplicationId
     * @param string|null $externalOrderNumber your own order number, e.g. the one the customer sees
     * @param string|null $number
     * @param string|null $status
     * @param string|null $statusCode
     * @param \DateTimeInterface|null $date
     * @param \DateTimeInterface|null $statusDate null without a waybill or before its first status
     * @param Waybill[] $waybills
     * @param float|null $priceNet
     * @param float|null $priceGross
     * @param float|null $unpaidAmount
     * @param Item[] $items empty unless included, see above
     * @param bool $paid
     * @param bool $invoice
     * @param string[]|null $courierServiceNames human-readable names of the additional services
     *        bought for the order
     * @param string|null $trackingUri
     * @param \DateTimeInterface|null $deliveredDate null until the order is delivered
     * @param string|null $courierName display name of the carrier, e.g. "UPS Standard"
     * @param float|null $rebateNet net value of the discount applied to the order, in PLN
     * @param float|null $rebateGross gross value of the discount applied to the order, in PLN
     * @param float|null $vatRate VAT rate applied to the order, as a percentage (23.0 for 23%)
     * @param string[]|null $label waybill numbers as a flat list. $waybills holds the same numbers
     *        as objects and also marks the primary waybill
     * @param string|null $shipmentCode code the sender presents at the drop-off point. Present only
     *        for orders using the label-less shipping service, and null until the carrier issues it
     * @param IndividualPricing|null $individualPricing individually negotiated pricing. Present only
     *        for freight (spedycja) orders
     * @param File[]|null $files documents attached to the order, see above
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_COURIER)]
        #[Validate\NotBlank]
        private string $courier,

        /**
         * @var ShipmentTypeEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_SHIPMENT_TYPE)]
        #[Validate\NotBlank]
        private ?ShipmentTypeEnum $shipmentType = null,

        /**
         * @var PackItem[]
         */
        #[Serializer\SerializedName(self::FIELD_PACKS)]
        #[Validate\NotEmptyList]
        #[Validate\Each(Validate\OfType::class, PackItem::class)]
        private array $packs = [],

        /**
         * @var Address|null
         */
        #[Serializer\SerializedName(self::FIELD_SENDER)]
        #[Validate\NotBlank]
        private ?Address $sender = null,

        /**
         * @var Address|null
         */
        #[Serializer\SerializedName(self::FIELD_RECIPIENT)]
        #[Validate\NotBlank]
        private ?Address $recipient = null,

        /**
         * @var Cod|null
         */
        #[Serializer\SerializedName(self::FIELD_CASH_ON_DELIVERY)]
        #[OmitWhenEmpty]
        private ?Cod $cod = null,

        /**
         * @var float|null
         */
        #[Serializer\SerializedName(self::FIELD_INSURANCE)]
        #[OmitWhenEmpty]
        #[Validate\NonNegative]
        private ?float $insurance = null,

        /**
         * @var Address|null
         */
        #[Serializer\SerializedName(self::FIELD_COVER_ADDRESS)]
        #[OmitWhenEmpty]
        private ?Address $coverAddress = null,

        /**
         * @var Pickup|null
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP)]
        #[OmitWhenEmpty]
        private ?Pickup $pickup = null,

        /**
         * Stored as a list. {@see self::getCourierServiceMap()} turns it into the flag map that is
         * sent.
         *
         * @var AdditionalServiceEnum[]
         */
        #[Serializer\Ignore]
        #[Validate\Each(Validate\OfType::class, AdditionalServiceEnum::class)]
        private array $courierService = [],

        /**
         * @var array<string, scalar>|null
         */
        #[Serializer\SerializedName(self::FIELD_ADDITIONAL_FIELDS)]
        #[OmitWhenEmpty]
        private ?array $additionalFields = [],

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        #[OmitWhenEmpty]
        private ?string $description = null,

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
        #[Serializer\SerializedName(self::FIELD_NUMBER)]
        #[ResponseOnly]
        private ?string $number = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_STATUS)]
        #[ResponseOnly]
        private ?string $status = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_STATUS_CODE)]
        #[ResponseOnly]
        private ?string $statusCode = null,

        /**
         * @var \DateTimeInterface|null
         */
        #[Serializer\SerializedName(self::FIELD_DATE)]
        #[ResponseOnly]
        private ?\DateTimeInterface $date = null,

        /**
         * @var \DateTimeInterface|null
         */
        #[Serializer\SerializedName(self::FIELD_STATUS_DATE)]
        #[ResponseOnly]
        private ?\DateTimeInterface $statusDate = null,

        /**
         * @var Waybill[]
         */
        #[Serializer\SerializedName(self::FIELD_WAYBILLS)]
        #[ResponseOnly]
        private array $waybills = [],

        /**
         * @var float|null
         */
        #[Serializer\SerializedName(self::FIELD_PRICE_NET)]
        #[ResponseOnly]
        private ?float $priceNet = null,

        /**
         * @var float|null
         */
        #[Serializer\SerializedName(self::FIELD_PRICE_GROSS)]
        #[ResponseOnly]
        private ?float $priceGross = null,

        /**
         * @var float|null
         */
        #[Serializer\SerializedName(self::FIELD_UNPAID_AMOUNT)]
        #[ResponseOnly]
        private ?float $unpaidAmount = null,

        /**
         * @var Item[]
         */
        #[Serializer\SerializedName(self::FIELD_ITEMS)]
        #[ResponseOnly]
        private array $items = [],

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_PAID)]
        #[ResponseOnly]
        private bool $paid = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_INVOICE)]
        #[ResponseOnly]
        private bool $invoice = false,

        /**
         * @var string[]|null
         */
        #[Serializer\SerializedName(self::FIELD_COURIER_SERVICE_NAMES)]
        #[ResponseOnly]
        private ?array $courierServiceNames = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_TRACKING_URI)]
        #[ResponseOnly]
        private ?string $trackingUri = null,

        /**
         * @var \DateTimeInterface|null
         */
        #[Serializer\SerializedName(self::FIELD_DELIVERED_DATE)]
        #[ResponseOnly]
        private ?\DateTimeInterface $deliveredDate = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_COURIER_NAME)]
        #[ResponseOnly]
        private ?string $courierName = null,

        /**
         * @var float|null
         */
        #[Serializer\SerializedName(self::FIELD_REBATE_NET)]
        #[ResponseOnly]
        private ?float $rebateNet = null,

        /**
         * @var float|null
         */
        #[Serializer\SerializedName(self::FIELD_REBATE_GROSS)]
        #[ResponseOnly]
        private ?float $rebateGross = null,

        /**
         * @var float|null
         */
        #[Serializer\SerializedName(self::FIELD_VAT_RATE)]
        #[ResponseOnly]
        private ?float $vatRate = null,

        /**
         * @var string[]|null
         */
        #[Serializer\SerializedName(self::FIELD_LABEL)]
        #[ResponseOnly]
        private ?array $label = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_SHIPMENT_CODE)]
        #[ResponseOnly]
        private ?string $shipmentCode = null,

        /**
         * @var IndividualPricing|null
         */
        #[Serializer\SerializedName(self::FIELD_INDIVIDUAL_PRICING)]
        #[ResponseOnly]
        private ?IndividualPricing $individualPricing = null,

        /**
         * @var File[]|null
         */
        #[Serializer\SerializedName(self::FIELD_FILES)]
        #[ResponseOnly]
        private ?array $files = null,
    ) {}

    /**
     * The services in the format the API expects: a flat map of service code to `true`.
     *
     * Only enabled services are included. Leaving a service out means the same as sending `false`.
     *
     * @return array<string, bool>
     */
    #[Serializer\SerializedName(self::FIELD_COURIER_SERVICE)]
    #[OmitWhenEmpty]
    public function getCourierServiceMap(): array
    {
        $map = [];
        foreach ($this->courierService as $service) {
            $map[$service->value] = true;
        }

        return $map;
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
     * @return static
     */
    public function setCourier(string $courier): static
    {
        $this->courier = $courier;

        return $this;
    }

    /**
     * @return ShipmentTypeEnum|null
     */
    public function getShipmentType(): ?ShipmentTypeEnum
    {
        return $this->shipmentType;
    }

    /**
     * @param ShipmentTypeEnum|null $shipmentType
     *
     * @return static
     */
    public function setShipmentType(?ShipmentTypeEnum $shipmentType): static
    {
        $this->shipmentType = $shipmentType;

        return $this;
    }

    /**
     * @return PackItem[]
     */
    public function getPacks(): array
    {
        return $this->packs;
    }

    /**
     * @param PackItem[] $packs
     *
     * @return static
     */
    public function setPacks(array $packs): static
    {
        $this->packs = $packs;

        return $this;
    }

    /**
     * @return Address|null
     */
    public function getSender(): ?Address
    {
        return $this->sender;
    }

    /**
     * @param Address|null $sender
     *
     * @return static
     */
    public function setSender(?Address $sender): static
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * @return Address|null
     */
    public function getRecipient(): ?Address
    {
        return $this->recipient;
    }

    /**
     * @param Address|null $recipient
     *
     * @return static
     */
    public function setRecipient(?Address $recipient): static
    {
        $this->recipient = $recipient;

        return $this;
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
     * @return static
     */
    public function setCod(?Cod $cod): static
    {
        $this->cod = $cod;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getInsurance(): ?float
    {
        return $this->insurance;
    }

    /**
     * @param float|null $insurance
     *
     * @return static
     */
    public function setInsurance(?float $insurance): static
    {
        $this->insurance = $insurance;

        return $this;
    }

    /**
     * @return Address|null
     */
    public function getCoverAddress(): ?Address
    {
        return $this->coverAddress;
    }

    /**
     * @param Address|null $coverAddress
     *
     * @return static
     */
    public function setCoverAddress(?Address $coverAddress): static
    {
        $this->coverAddress = $coverAddress;

        return $this;
    }

    /**
     * @return Pickup|null
     */
    public function getPickup(): ?Pickup
    {
        return $this->pickup;
    }

    /**
     * @param Pickup|null $pickup
     *
     * @return static
     */
    public function setPickup(?Pickup $pickup): static
    {
        $this->pickup = $pickup;

        return $this;
    }

    /**
     * @return AdditionalServiceEnum[]
     */
    public function getCourierService(): array
    {
        return $this->courierService;
    }

    /**
     * @param AdditionalServiceEnum[] $courierService
     *
     * @return static
     */
    public function setCourierService(array $courierService): static
    {
        $this->courierService = $courierService;

        return $this;
    }

    /**
     * @return array<string, scalar>|null
     */
    public function getAdditionalFields(): ?array
    {
        return $this->additionalFields;
    }

    /**
     * @param array<string, scalar>|null $additionalFields
     *
     * @return static
     */
    public function setAdditionalFields(?array $additionalFields): static
    {
        $this->additionalFields = $additionalFields;

        return $this;
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
     * @return static
     */
    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getExternalApplicationId(): ?string
    {
        return $this->externalApplicationId;
    }

    /**
     * @param string|null $externalApplicationId
     *
     * @return static
     */
    public function setExternalApplicationId(?string $externalApplicationId): static
    {
        $this->externalApplicationId = $externalApplicationId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getExternalOrderId(): ?string
    {
        return $this->externalOrderId;
    }

    /**
     * @param string|null $externalOrderId
     *
     * @return static
     */
    public function setExternalOrderId(?string $externalOrderId): static
    {
        $this->externalOrderId = $externalOrderId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getExternalOrderNumber(): ?string
    {
        return $this->externalOrderNumber;
    }

    /**
     * @param string|null $externalOrderNumber
     *
     * @return static
     */
    public function setExternalOrderNumber(?string $externalOrderNumber): static
    {
        $this->externalOrderNumber = $externalOrderNumber;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getNumber(): ?string
    {
        return $this->number;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @return string|null
     */
    public function getStatusCode(): ?string
    {
        return $this->statusCode;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getStatusDate(): ?\DateTimeInterface
    {
        return $this->statusDate;
    }

    /**
     * @return Waybill[]
     */
    public function getWaybills(): array
    {
        return $this->waybills;
    }

    /**
     * @return float|null
     */
    public function getPriceNet(): ?float
    {
        return $this->priceNet;
    }

    /**
     * @return float|null
     */
    public function getPriceGross(): ?float
    {
        return $this->priceGross;
    }

    /**
     * @return float|null
     */
    public function getUnpaidAmount(): ?float
    {
        return $this->unpaidAmount;
    }

    /**
     * @return Item[]
     */
    public function getItems(): array
    {
        return $this->items;
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
     * @return string[]|null
     */
    public function getCourierServiceNames(): ?array
    {
        return $this->courierServiceNames;
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
     * @return string|null
     */
    public function getCourierName(): ?string
    {
        return $this->courierName;
    }

    /**
     * @return float|null
     */
    public function getRebateNet(): ?float
    {
        return $this->rebateNet;
    }

    /**
     * @return float|null
     */
    public function getRebateGross(): ?float
    {
        return $this->rebateGross;
    }

    /**
     * @return float|null
     */
    public function getVatRate(): ?float
    {
        return $this->vatRate;
    }

    /**
     * @return string[]|null
     */
    public function getLabel(): ?array
    {
        return $this->label;
    }

    /**
     * @return string|null
     */
    public function getShipmentCode(): ?string
    {
        return $this->shipmentCode;
    }

    /**
     * @return IndividualPricing|null
     */
    public function getIndividualPricing(): ?IndividualPricing
    {
        return $this->individualPricing;
    }

    /**
     * @return File[]|null
     */
    public function getFiles(): ?array
    {
        return $this->files;
    }
}
