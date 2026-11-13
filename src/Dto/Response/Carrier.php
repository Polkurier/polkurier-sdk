<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Dto\Response\Carrier\AdditionalData;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A carrier this account may ship with.
 *
 * Quote the service code when pricing or placing an order. The additional data describes what the
 * carrier accepts. It is filled in only when the listing asked for it.
 *
 * @api
 */
final readonly class Carrier implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_SERVICE_CODE = 'servicecode';
    public const string FIELD_NAME = 'name';
    public const string FIELD_FOREIGN_SHIPMENTS = 'foreign_shipments';
    public const string FIELD_ADDITIONAL_DATA = 'additional_data';
    /**-@#*/

    /**
     * @param string $serviceCode
     * @param string $name
     * @param bool $foreignShipments
     * @param AdditionalData|null $additionalData
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_SERVICE_CODE)]
        public string $serviceCode,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        public string $name,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_FOREIGN_SHIPMENTS)]
        public bool $foreignShipments,

        /**
         * @var AdditionalData|null null unless the listing asked for the additional data
         */
        #[Serializer\SerializedName(self::FIELD_ADDITIONAL_DATA)]
        #[OmitWhenEmpty]
        public ?AdditionalData $additionalData = null,
    ) {}
}
