<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\TwoFactorToken;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Whether a submitted two-factor code was accepted.
 */
readonly class ValidityMessage implements MessageInterface
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
        private bool $valid,
    ) {}

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->valid;
    }
}
