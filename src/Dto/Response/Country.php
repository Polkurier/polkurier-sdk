<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A country the service supports, with its regions.
 */
final readonly class Country implements MessageInterface
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
         * ISO country code.
         *
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        public string $id,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        public string $name,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_UE)]
        public bool $ue = false,

        /**
         * @var bool true for Poland only
         */
        #[Serializer\SerializedName(self::FIELD_DEFAULT)]
        public bool $default = false,

        /**
         * @var Province[]
         */
        #[Serializer\SerializedName(self::FIELD_PROVINCES)]
        public array $provinces = [],
    ) {}
}
