<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Account;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Starts an identity verification, or returns the one in progress.
 *
 * Pass an order number to tie the verification to that shipment. Without one, the API starts a
 * general account verification.
 */
final readonly class RegisterAccountVerificationMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ORDER_NO = 'orderno';
    /**-@#*/

    /**
     * @param string|null $orderNo the order prompting verification, if any
     */
    public function __construct(
        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_ORDER_NO)]
        #[Validate\OrderNumber]
        #[OmitWhenEmpty]
        private ?string $orderNo = null,
    ) {}

    /**
     * @return string|null
     */
    public function getOrderNo(): ?string
    {
        return $this->orderNo;
    }
}
