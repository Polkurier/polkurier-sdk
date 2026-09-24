<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Carrier\AdditionalData;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

readonly class Property implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_AVAILABLE = 'available';
    public const string FIELD_DESCRIPTION = 'description';
    public const string FIELD_DEFAULT = 'default';
    /**-@#*/

    /**
     * @param bool $available
     * @param string $description
     * @param bool|null $default
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
         * @var bool|null
         */
        #[Serializer\SerializedName(self::FIELD_DEFAULT)]
        #[OmitWhenEmpty]
        private ?bool $default = null,
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
    public function isDefault(): bool
    {
        return (bool)$this->default;
    }
}
