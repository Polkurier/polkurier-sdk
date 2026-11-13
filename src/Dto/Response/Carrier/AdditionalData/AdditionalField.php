<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Carrier\AdditionalData;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Courier\AdditionalFieldTypeEnum;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A carrier-specific field an order can carry, sent in
 * {@see \Polkurier\Webservice\Entity\Order\CreateOrderMessage}'s additional fields under its name.
 */
final readonly class AdditionalField implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_NAME = 'name';
    public const string FIELD_LABEL = 'label';
    public const string FIELD_DESCRIPTION = 'description';
    public const string FIELD_TYPE = 'type';
    public const string FIELD_REQUIRED = 'required';
    public const string FIELD_ENABLED = 'enabled';
    public const string FIELD_OPTIONS = 'options';
    /**-@#*/

    /**
     * @param string $name
     * @param string $label
     * @param string $description
     * @param AdditionalFieldTypeEnum|null $type
     * @param bool $required
     * @param bool $enabled
     * @param AdditionalFieldOption[]|null $options the allowed values, null when any value goes
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        private string $name,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_LABEL)]
        private string $label,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        private string $description,

        /**
         * @var AdditionalFieldTypeEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        private ?AdditionalFieldTypeEnum $type,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_REQUIRED)]
        private bool $required,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_ENABLED)]
        private bool $enabled,

        /**
         * @var AdditionalFieldOption[]|null
         */
        #[Serializer\SerializedName(self::FIELD_OPTIONS)]
        private ?array $options = null,
    ) {}

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
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return AdditionalFieldTypeEnum|null null for a value this version of the SDK doesn't know
     */
    public function getType(): ?AdditionalFieldTypeEnum
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return AdditionalFieldOption[]|null the allowed values, null when any value goes
     */
    public function getOptions(): ?array
    {
        return $this->options;
    }
}
