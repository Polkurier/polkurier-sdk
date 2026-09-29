<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Order\AdditionalServiceEnum;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Message\CreateOrderMessageValidator;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The payload for `create_order` and `order_valuation_v2`.
 *
 * Both methods take the same message, but a payload that prices successfully can still be rejected
 * when you place the order. A successful quote does not mean the order is valid.
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
 * - `description` has a length limit of {@see self::DESCRIPTION_MAX_LENGTH}, and some carriers
 *   allow less. Keep it short.
 */
#[Validate\ValidatedBy(CreateOrderMessageValidator::class)]
readonly class CreateOrderMessage implements MessageInterface
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
    public const string FIELD_COUPON_CODE = 'coupon_code';
    /**-@#*/

    /**
     * Maximum length of `description`, unless the carrier sets a lower limit.
     */
    public const int DESCRIPTION_MAX_LENGTH = 30;

    /**
     * @param string $courier carrier code, as returned by `available_carriers`
     * @param ShipmentTypeEnum $shipmentType
     * @param PackMessage[] $packs at least one
     * @param OrderAddressMessage $sender
     * @param OrderAddressMessage $recipient
     * @param CodMessage|null $cod cash on delivery. An amount of zero means no
     *                                      collection, and the other fields are then not needed
     * @param float|null $insurance declared value in PLN, zero for no insurance
     * @param CoverAddressMessage|null $coverAddress alternative return address
     * @param PickupMessage|null $pickup
     * @param AdditionalServiceEnum[] $courierService services to order, sent as the flag map the
     *                                                API expects
     * @param array<string, string> $additionalFields carrier-specific extra fields
     * @param string|null $description
     * @param string|null $couponCode
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_COURIER)]
        #[Validate\NotBlank]
        private string $courier,

        /**
         * @var ShipmentTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_SHIPMENT_TYPE)]
        private ShipmentTypeEnum $shipmentType,

        /**
         * @var PackMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_PACKS)]
        #[Validate\NotEmptyList]
        #[Validate\Valid]
        #[Validate\Each(Validate\OfType::class, PackMessage::class)]
        private array $packs,

        /**
         * @var OrderAddressMessage
         */
        #[Serializer\SerializedName(self::FIELD_SENDER)]
        #[Validate\Valid]
        private OrderAddressMessage $sender,

        /**
         * @var OrderAddressMessage
         */
        #[Serializer\SerializedName(self::FIELD_RECIPIENT)]
        #[Validate\Valid]
        private OrderAddressMessage $recipient,

        /**
         * @var CodMessage|null
         */
        #[Serializer\SerializedName(self::FIELD_CASH_ON_DELIVERY)]
        #[OmitWhenEmpty]
        #[Validate\Valid]
        private ?CodMessage $cod = null,

        /**
         * @var float|null
         */
        #[Serializer\SerializedName(self::FIELD_INSURANCE)]
        #[OmitWhenEmpty]
        #[Validate\NonNegative]
        private ?float $insurance = null,

        /**
         * @var CoverAddressMessage|null
         */
        #[Serializer\SerializedName(self::FIELD_COVER_ADDRESS)]
        #[OmitWhenEmpty]
        #[Validate\Valid]
        private ?CoverAddressMessage $coverAddress = null,

        /**
         * @var PickupMessage|null
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP)]
        #[OmitWhenEmpty]
        #[Validate\Valid]
        private ?PickupMessage $pickup = null,

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
         * @var array<string, string>
         */
        #[Serializer\SerializedName(self::FIELD_ADDITIONAL_FIELDS)]
        #[OmitWhenEmpty]
        private array $additionalFields = [],

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        #[OmitWhenEmpty]
        private ?string $description = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_COUPON_CODE)]
        #[OmitWhenEmpty]
        private ?string $couponCode = null,
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
     * @return ShipmentTypeEnum
     */
    public function getShipmentType(): ShipmentTypeEnum
    {
        return $this->shipmentType;
    }

    /**
     * @return PackMessage[]
     */
    public function getPacks(): array
    {
        return $this->packs;
    }

    /**
     * @return OrderAddressMessage
     */
    public function getSender(): OrderAddressMessage
    {
        return $this->sender;
    }

    /**
     * @return OrderAddressMessage
     */
    public function getRecipient(): OrderAddressMessage
    {
        return $this->recipient;
    }

    /**
     * @return CodMessage|null
     */
    public function getCod(): ?CodMessage
    {
        return $this->cod;
    }

    /**
     * @return float|null
     */
    public function getInsurance(): ?float
    {
        return $this->insurance;
    }

    /**
     * @return CoverAddressMessage|null
     */
    public function getCoverAddress(): ?CoverAddressMessage
    {
        return $this->coverAddress;
    }

    /**
     * @return PickupMessage|null
     */
    public function getPickup(): ?PickupMessage
    {
        return $this->pickup;
    }

    /**
     * @return AdditionalServiceEnum[]
     */
    public function getCourierService(): array
    {
        return $this->courierService;
    }

    /**
     * @return array<string, string>
     */
    public function getAdditionalFields(): array
    {
        return $this->additionalFields;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return string|null
     */
    public function getCouponCode(): ?string
    {
        return $this->couponCode;
    }
}
