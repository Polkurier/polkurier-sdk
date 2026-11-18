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
 *
 * @api
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
        #[Serializer\SerializedName(self::FIELD_AVAILABLE)]
        public bool $available,

        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        public string $description,

        #[Serializer\SerializedName(self::FIELD_REQUIRE_INSURANCE)]
        public bool $requireInsurance,
    ) {}
}
