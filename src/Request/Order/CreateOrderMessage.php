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
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Message\CreateOrderMessageValidator;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The payload for `create_order` and `order_valuation_v2`.
 *
 * One message covers both methods, which does not mean both will accept it: a payload that prices
 * successfully can still be rejected when you go on to place the order. Treat a successful quote as
 * a price, not as validation.
 *
 * Requirements this class cannot check on its own, because they vary by carrier or by account:
 *
 * - `pickup`. Some carriers need a pickup date, some also a time window. `available_carriers`
 *   reports which, per carrier.
 * - {@see AdditionalServiceEnum::WeekCollection} is only available for InPost parcel lockers.
 * - {@see AdditionalServiceEnum::SmsNotificationRecipientWithName} requires a default SMS sender
 *   name on the account, and takes the place of SMS_NOTIFICATION_RECIPIENT rather than adding to
 *   it — send one or the other.
 * - `description` has a length limit: {@see self::DESCRIPTION_MAX_LENGTH} in general, and possibly
 *   shorter for a given carrier. A longer value is not reported as an error, so keep it short
 *   rather than relying on it arriving intact.
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
     * Length limit for `description` where the carrier imposes no stricter one.
     */
    public const int DESCRIPTION_MAX_LENGTH = 30;

    /**
     * @param string $courier carrier code, as returned by `available_carriers`
     * @param ShipmentTypeEnum $shipmentType
     * @param PackMessage[] $packs at least one
     * @param OrderAddressMessage $sender
     * @param OrderAddressMessage $recipient
     * @param CodMessage|null $cod cash on delivery; an amount of zero means no
     *                                      collection and the rest of the block does not apply
     * @param float|null $insurance declared value in PLN; zero means no insurance
     * @param CoverAddressMessage|null $coverAddress alternative return address
     * @param PickupMessage|null $pickup
     * @param AdditionalServiceEnum[] $courierService services to buy; serialized as the flag map
     *                                                the API expects
     * @param array<string, string> $additionalFields carrier-specific extra fields
     * @param string|null $description
     * @param string|null $couponCode
     *
     * @throws InvalidArgumentException when $packs or $courierService hold the wrong types
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
         * Held as a list for a usable API; serialized as a flag map by
         * {@see self::getCourierServiceMap()}.
         *
         * @var AdditionalServiceEnum[]
         */
        #[Serializer\Ignore]
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
    ) {
        $this->validatePacks();
        $this->validateCourierService();
    }

    /**
     * The services as the API wants them: a flat map of service code to `true`.
     *
     * Only enabled services are sent: omitting a service and sending it as `false` mean the same
     * thing, so there is nothing to gain by sending the disabled ones.
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

    /**
     * @return void
     * @throws InvalidArgumentException
     */
    private function validatePacks(): void
    {
        if ($this->packs === []) {
            throw new InvalidArgumentException(\sprintf(
                '"%s" field requires at least one pack.',
                self::FIELD_PACKS
            ));
        }

        foreach ($this->packs as $pack) {
            if (!($pack instanceof PackMessage)) {
                throw new InvalidArgumentException(\sprintf(
                    '"%s" field may only contain instances of %s, %s provided.',
                    self::FIELD_PACKS,
                    PackMessage::class,
                    \get_debug_type($pack)
                ));
            }
        }
    }

    /**
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateCourierService(): void
    {
        foreach ($this->courierService as $service) {
            if (!($service instanceof AdditionalServiceEnum)) {
                throw new InvalidArgumentException(\sprintf(
                    '"%s" field may only contain instances of %s, %s provided.',
                    self::FIELD_COURIER_SERVICE,
                    AdditionalServiceEnum::class,
                    \get_debug_type($service)
                ));
            }
        }
    }
}
