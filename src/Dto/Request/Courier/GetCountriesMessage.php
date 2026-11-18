<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Courier;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Asks which countries a given carrier serves.
 *
 * @api
 */
final readonly class GetCountriesMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_COURIERS = 'couriers';
    /**-@#*/

    /**
     * @param string $couriers
     */
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_COURIERS)]
        #[Validate\NotBlank]
        public string $couriers,
    ) {}
}
