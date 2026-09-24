<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Account;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Whether a submitted security PIN was correct.
 *
 * A wrong PIN is a verdict, not an error, so check the flag rather than relying on an exception.
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
