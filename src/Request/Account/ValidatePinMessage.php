<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Account;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Checks a security PIN without changing anything.
 *
 * Use it to confirm a PIN in your UI before an operation that needs it.
 */
readonly class ValidatePinMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_PIN = 'pin';
    /**-@#*/

    /**
     * @param string $pin the PIN to check
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_PIN)]
        #[Validate\NotBlank]
        private string $pin,
    ) {}

    /**
     * @return string
     */
    public function getPin(): string
    {
        return $this->pin;
    }
}
