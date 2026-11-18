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
 * Whether a carrier offers something, such as insurance or an additional service.
 *
 * @api
 */
final readonly class Property implements MessageInterface
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
     * @param bool $default
     */
    public function __construct(
        /**
         * Whether the carrier offers it.
         */
        #[Serializer\SerializedName(self::FIELD_AVAILABLE)]
        public bool $available,

        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        public string $description,

        /**
         * Whether this is on by default for the carrier.
         *
         * @var bool false when the carrier doesn't say
         */
        #[Serializer\SerializedName(self::FIELD_DEFAULT)]
        public bool $default = false,
    ) {}
}
