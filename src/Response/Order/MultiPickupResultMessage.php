<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Per-order outcome of `create_multi_order_pickup`.
 *
 * The call is partial by design: a pickup can be booked for some of the submitted waybills and
 * refused for others, so both lists must be inspected. A response with an empty `success` list is
 * still reported as an API success.
 */
readonly class MultiPickupResultMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_SUCCESS = 'success';
    public const string FIELD_FAILED = 'failed';
    /**-@#*/

    /**
     * @param string[] $success order numbers a pickup was booked for
     * @param array<string, string> $failed reason for each rejection, keyed by waybill number
     */
    public function __construct(
        /**
         * @var string[]
         */
        #[Serializer\SerializedName(self::FIELD_SUCCESS)]
        private array $success = [],

        /**
         * @var array<string, string>
         */
        #[Serializer\SerializedName(self::FIELD_FAILED)]
        private array $failed = [],
    ) {}

    /**
     * @return string[]
     */
    public function getSuccess(): array
    {
        return $this->success;
    }

    /**
     * @return array<string, string>
     */
    public function getFailed(): array
    {
        return $this->failed;
    }
}
