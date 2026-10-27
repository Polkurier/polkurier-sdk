<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Account;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Carries one of the account's PINs.
 *
 * {@see \Polkurier\Webservice\Request\Account\ChangePinMessage} carries the telephone PIN, {@see
 * \Polkurier\Webservice\Request\Account\ValidatePinMessage} the security PIN, a different value.
 */
readonly class PinMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_PIN = 'pin';
    /**-@#*/

    /**
     * @param string $pin
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
