<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\System;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Rules governing the security PIN and two-factor codes.
 *
 * These are policy and can change without a new API version.
 */
readonly class SecurityLimitsMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_USER_PIN_MIN_LENGTH = 'user_pin_min_length';
    public const string FIELD_USER_PIN_MAX_LENGTH = 'user_pin_max_length';
    public const string FIELD_TWO_FACTOR_CODE_LENGTH = 'two_factor_code_length';
    public const string FIELD_TWO_FACTOR_RESEND_THROTTLE = 'two_factor_resend_throttle';
    /**-@#*/

    /**
     * @param int $userPinMinLength shortest allowed security PIN
     * @param int $userPinMaxLength longest allowed security PIN
     * @param int $twoFactorCodeLength exact length of a two-factor code
     * @param int $twoFactorResendThrottle seconds that must pass before a code may be resent.
     *                                     Asking sooner is refused with TWO_FACTOR_ALREADY_SENT
     */
    public function __construct(
        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_USER_PIN_MIN_LENGTH)]
        private int $userPinMinLength = 0,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_USER_PIN_MAX_LENGTH)]
        private int $userPinMaxLength = 0,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_TWO_FACTOR_CODE_LENGTH)]
        private int $twoFactorCodeLength = 0,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_TWO_FACTOR_RESEND_THROTTLE)]
        private int $twoFactorResendThrottle = 0,
    ) {}

    /**
     * @return int
     */
    public function getUserPinMinLength(): int
    {
        return $this->userPinMinLength;
    }

    /**
     * @return int
     */
    public function getUserPinMaxLength(): int
    {
        return $this->userPinMaxLength;
    }

    /**
     * @return int
     */
    public function getTwoFactorCodeLength(): int
    {
        return $this->twoFactorCodeLength;
    }

    /**
     * @return int
     */
    public function getTwoFactorResendThrottle(): int
    {
        return $this->twoFactorResendThrottle;
    }
}
