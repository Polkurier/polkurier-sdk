<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Enum\Courier\PointFunctionEnum;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A parcel locker or pickup point.
 *
 * Put the id in an address's point id field. Check the functions before offering a point for a
 * particular use. Not every point accepts drop-offs, collections or cash on delivery.
 */
final readonly class CourierPoint implements MessageInterface, ParsedResponseInterface
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
     * @param PointFunctionEnum[] $functions what the point can be used for
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
        public string $id,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        public string $type = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        public string $name = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_PROVIDER)]
        public string $provider = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CITY)]
        public string $city = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ZIP)]
        public string $zip = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_STREET)]
        public string $street = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        public string $description = '',

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_LATITUDE)]
        public float $latitude = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_LONGITUDE)]
        public float $longitude = 0.0,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_COD)]
        public bool $cod = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_AVAILABLE)]
        public bool $available = false,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_STATUS)]
        public string $status = '',

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_SEND)]
        public bool $send = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_COLLECT)]
        public bool $collect = false,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_OPENING_HOURS)]
        public string $openingHours = '',

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_VISIBLE)]
        public bool $visible = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_REQUIRE_APP)]
        public bool $requireApp = false,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_REQUIRE_APP_MESSAGE)]
        public string $requireAppMessage = '',

        /**
         * What the point can be used for, each function once.
         *
         * @var PointFunctionEnum[]
         */
        #[Serializer\SerializedName(self::FIELD_FUNCTIONS)]
        public array $functions = [],

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_COUNTRY_ISO)]
        public string $countryIso = '',

        /**
         * Postcode, city and street on one line, ready to display.
         *
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ADDRESS)]
        public string $address = '',

        /**
         * Polkurier's numeric id for the carrier operating this point.
         *
         * This is not the API carrier code. For the value the order methods accept, use
         * {@see self::$provider}.
         *
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_COURIER_ID)]
        public int $courierId = 0,

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}
}
