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
 * One allowed value of an {@see AdditionalField}.
 *
 * @api
 */
final readonly class AdditionalFieldOption implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_VALUE = 'value';
    public const string FIELD_LABEL = 'label';
    /**-@#*/

    /**
     * @param string $value what to send
     * @param string $label what to show
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_VALUE)]
        public string $value,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_LABEL)]
        public string $label,
    ) {}
}
