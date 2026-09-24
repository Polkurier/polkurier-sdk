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
 * Changes the account password.
 *
 * The current password must be supplied and is checked first: without it, or with the wrong one, the
 * API refuses the change as forbidden rather than as a validation failure.
 */
readonly class ChangePasswordMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_PASSWORD = 'password';
    public const string FIELD_PREVIOUS_PASSWORD = 'previous_password';
    /**-@#*/

    /**
     * @param string $password the new password
     * @param string $previousPassword the current password
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_PASSWORD)]
        #[Validate\NotBlank]
        private string $password,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_PREVIOUS_PASSWORD)]
        #[Validate\NotBlank]
        private string $previousPassword,
    ) {}

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getPreviousPassword(): string
    {
        return $this->previousPassword;
    }
}
