<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\System;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Narrows the carrier notices to one carrier.
 *
 * @api
 */
final readonly class GetCouriersMessagesMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_COURIER = 'courier';
    /**-@#*/

    /**
     * @param string|null $courier carrier code, e.g. `DPD`
     */
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_COURIER)]
        #[OmitWhenEmpty]
        public ?string $courier = null,
    ) {}
}
