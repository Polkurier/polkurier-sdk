<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Response\Carrier\AdditionalData;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A carrier this account may ship with.
 *
 * Quote the service code when pricing or placing an order. The additional data describes what the
 * carrier accepts. It is filled in only when the listing asked for it.
 */
readonly class Carrier implements MessageInterface
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
        private string $serviceCode,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        private string $name,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_FOREIGN_SHIPMENTS)]
        private bool $foreignShipments,

        /**
         * @var AdditionalData|null
         */
        #[Serializer\SerializedName(self::FIELD_ADDITIONAL_DATA)]
        #[OmitWhenEmpty]
        private ?AdditionalData $additionalData = null,
    ) {}

    /**
     * @return string
     */
    public function getServiceCode(): string
    {
        return $this->serviceCode;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isForeignShipments(): bool
    {
        return $this->foreignShipments;
    }

    /**
     * @return AdditionalData|null null unless the listing asked for the additional data
     */
    public function getAdditionalData(): ?AdditionalData
    {
        return $this->additionalData;
    }
}
