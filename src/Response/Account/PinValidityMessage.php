<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Account;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Whether a submitted security PIN was correct.
 *
 * A wrong PIN is not an API error and throws no exception. Check the flag.
 */
readonly class PinValidityMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_VALID = 'valid';
    /**-@#*/

    /**
     * @param bool $valid
     */
    public function __construct(
        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_VALID)]
        private bool $valid = false,
    ) {}

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->valid;
    }
}
