<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Carrier\AdditionalData;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Whether a carrier offers cash on delivery, and whether it then requires insurance.
 */
final readonly class Cod implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_AVAILABLE = 'available';
    public const string FIELD_DESCRIPTION = 'description';
    public const string FIELD_REQUIRE_INSURANCE = 'require_insurance';
    /**-@#*/

    /**
     * @param bool $available
     * @param string $description
     * @param bool $requireInsurance
     */
    public function __construct(
        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_AVAILABLE)]
        private bool $available,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        private string $description,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_REQUIRE_INSURANCE)]
        private bool $requireInsurance,
    ) {}

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
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return bool
     */
    public function isRequireInsurance(): bool
    {
        return $this->requireInsurance;
    }
}
