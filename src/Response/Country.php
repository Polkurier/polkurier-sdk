<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A country the service supports, with its regions.
 */
readonly class Country implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    public const string FIELD_NAME = 'name';
    public const string FIELD_UE = 'is_ue';
    public const string FIELD_DEFAULT = 'is_default';
    public const string FIELD_PROVINCES = 'provinces';
    /**-@#*/

    /**
     * @param string $id
     * @param string $name
     * @param bool $ue
     * @param bool $default
     * @param Province[] $provinces
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
        #[Serializer\SerializedName(self::FIELD_NAME)]
        private string $name,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_UE)]
        private bool $ue = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_DEFAULT)]
        private bool $default = false,

        /**
         * @var Province[]
         */
        #[Serializer\SerializedName(self::FIELD_PROVINCES)]
        private array $provinces = [],
    ) {}

    /**
     * ISO country code.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
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
    public function isUe(): bool
    {
        return $this->ue;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->default;
    }

    /**
     * @return Province[]
     */
    public function getProvinces(): array
    {
        return $this->provinces;
    }
}
