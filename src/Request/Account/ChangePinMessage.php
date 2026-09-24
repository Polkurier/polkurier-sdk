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
 * Sets or changes the account's telephone PIN.
 *
 * This is the short code quoted when contacting support, and the same code that unlocks PIN-guarded
 * operations. Its permitted length is reported by `get_system_info` under `security`.
 */
readonly class ChangePinMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_PIN = 'pin';
    /**-@#*/

    /**
     * @param string $pin the new PIN
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
