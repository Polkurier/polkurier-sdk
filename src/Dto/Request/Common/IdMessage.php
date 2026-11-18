<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Common;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Identifies a record by its id.
 *
 * @api
 */
abstract readonly class IdMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    /**-@#*/

    /**
     * @param string $id
     */
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_ID)]
        #[Validate\NotBlank]
        public string $id,
    ) {}
}
