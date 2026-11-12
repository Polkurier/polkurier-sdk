<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Enum\Courier\PointFunctionEnum;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A parcel locker or pickup point.
 *
 * Put the id in an address's point id field. Check the functions before offering a point for a
 * particular use. Not every point accepts drop-offs, collections or cash on delivery.
 */
readonly class CourierPoint implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    public const string FIELD_TYPE = 'type';
    public const string FIELD_NAME = 'name';
    public const string FIELD_PROVIDER = 'provider';
    public const string FIELD_CITY = 'city';
    public const string FIELD_ZIP = 'zip';
    public const string FIELD_STREET = 'street';
    public const string FIELD_DESCRIPTION = 'description';
    public const string FIELD_LATITUDE = 'latitude';
    public const string FIELD_LONGITUDE = 'longitude';
    public const string FIELD_COD = 'cod';
    public const string FIELD_AVAILABLE = 'available';
    public const string FIELD_STATUS = 'status';
    public const string FIELD_SEND = 'send';
    public const string FIELD_COLLECT = 'collect';
    public const string FIELD_OPENING_HOURS = 'openingHours';
    public const string FIELD_VISIBLE = 'visible';
    public const string FIELD_REQUIRE_APP = 'requireApp';
    public const string FIELD_REQUIRE_APP_MESSAGE = 'requireAppMessage';
    public const string FIELD_FUNCTIONS = 'functions';
    public const string FIELD_COUNTRY_ISO = 'countryiso';
    public const string FIELD_ADDRESS = 'address';
    public const string FIELD_COURIER_ID = 'courierId';
    /**-@#*/

    /**
     * @param string $id
     * @param string $type
     * @param string $name
     * @param string $provider
     * @param string $city
     * @param string $zip
     * @param string $street
     * @param string $description
     * @param float $latitude
     * @param float $longitude
     * @param bool $cod
     * @param bool $available
     * @param string $status
     * @param bool $send
     * @param bool $collect
     * @param string $openingHours
     * @param bool $visible
     * @param bool $requireApp
     * @param string $requireAppMessage
     * @param string[] $functions what the point can be used for, as the API sends it
     * @param string $countryIso
     * @param string $address postcode, city and street on one line
     * @param int $courierId Polkurier's numeric carrier id, not the API carrier code
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        private string $id,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        private string $type = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        private string $name = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_PROVIDER)]
        private string $provider = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CITY)]
        private string $city = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ZIP)]
        private string $zip = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_STREET)]
        private string $street = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        private string $description = '',

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_LATITUDE)]
        private float $latitude = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_LONGITUDE)]
        private float $longitude = 0.0,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_COD)]
        private bool $cod = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_AVAILABLE)]
        private bool $available = false,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_STATUS)]
        private string $status = '',

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_SEND)]
        private bool $send = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_COLLECT)]
        private bool $collect = false,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_OPENING_HOURS)]
        private string $openingHours = '',

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_VISIBLE)]
        private bool $visible = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_REQUIRE_APP)]
        private bool $requireApp = false,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_REQUIRE_APP_MESSAGE)]
        private string $requireAppMessage = '',

        /**
         * @var string[]
         */
        #[Serializer\SerializedName(self::FIELD_FUNCTIONS)]
        private array $functions = [],

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_COUNTRY_ISO)]
        private string $countryIso = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ADDRESS)]
        private string $address = '',

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_COURIER_ID)]
        private int $courierId = 0,

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @return string
     */
    public function getZip(): string
    {
        return $this->zip;
    }

    /**
     * @return string
     */
    public function getStreet(): string
    {
        return $this->street;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return float
     */
    public function getLatitude(): float
    {
        return $this->latitude;
    }

    /**
     * @return float
     */
    public function getLongitude(): float
    {
        return $this->longitude;
    }

    /**
     * @return bool
     */
    public function isCod(): bool
    {
        return $this->cod;
    }

    /**
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->available;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return bool
     */
    public function isSend(): bool
    {
        return $this->send;
    }

    /**
     * @return bool
     */
    public function isCollect(): bool
    {
        return $this->collect;
    }

    /**
     * @return string
     */
    public function getOpeningHours(): string
    {
        return $this->openingHours;
    }

    /**
     * @return bool
     */
    public function isVisible(): bool
    {
        return $this->visible;
    }

    /**
     * @return bool
     */
    public function isRequireApp(): bool
    {
        return $this->requireApp;
    }

    /**
     * @return string
     */
    public function getRequireAppMessage(): string
    {
        return $this->requireAppMessage;
    }

    /**
     * What the point can be used for.
     *
     * A point can also list functions specific to its carrier. Those, and any other value the enum
     * doesn't have, are left out. Each function appears once.
     *
     * @return PointFunctionEnum[]
     */
    public function getFunctions(): array
    {
        $functions = [];
        foreach ($this->functions as $function) {
            $case = \is_string($function) ? PointFunctionEnum::tryFrom($function) : null;
            if ($case !== null && !\in_array($case, $functions, true)) {
                $functions[] = $case;
            }
        }

        return $functions;
    }

    /**
     * @return string
     */
    public function getCountryIso(): string
    {
        return $this->countryIso;
    }

    /**
     * Postcode, city and street on one line, ready to display.
     *
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * Polkurier's numeric id for the carrier operating this point.
     *
     * This is not the API carrier code. For the value the order methods accept, use
     * {@see self::getProvider()}.
     *
     * @return int
     */
    public function getCourierId(): int
    {
        return $this->courierId;
    }
}
