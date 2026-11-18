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
 * {@see \Polkurier\Webservice\Dto\Request\Order\CreateOrderMessage}'s additional fields under its name.
 *
 * @api
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
        #[Serializer\SerializedName(self::FIELD_NAME)]
        public string $name,

        #[Serializer\SerializedName(self::FIELD_LABEL)]
        public string $label,

        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        public string $description,

        /**
         * @var AdditionalFieldTypeEnum|null null for a value the SDK doesn't know
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        public ?AdditionalFieldTypeEnum $type,

        #[Serializer\SerializedName(self::FIELD_REQUIRED)]
        public bool $required,

        #[Serializer\SerializedName(self::FIELD_ENABLED)]
        public bool $enabled,

        /**
         * @var AdditionalFieldOption[]|null the allowed values, null when any value goes
         */
        #[Serializer\SerializedName(self::FIELD_OPTIONS)]
        public ?array $options = null,
    ) {}
}
