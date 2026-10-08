<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\MoneyBox;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Identifies the payout to cancel.
 */
readonly class CancelMoneyBoxPayoutMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_PAYOUT_NUMBER = 'payout_number';
    /**-@#*/

    /**
     * @param string $payoutNumber payout reference
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_PAYOUT_NUMBER)]
        #[Validate\NotBlank]
        private string $payoutNumber,
    ) {}

    /**
     * @return string
     */
    public function getPayoutNumber(): string
    {
        return $this->payoutNumber;
    }
}
