<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\System;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The verdict on a discount coupon.
 *
 * {@see self::$valid} says the coupon exists and the account may use it.
 * {@see self::$validForOrder} says it also applies to the order sent with the request. When no
 * order was sent, the second is always false.
 *
 * For a code that does not exist, the descriptive fields are null and the description is empty.
 */
final readonly class CouponValidationMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_CODE = 'code';
    public const string FIELD_VALID = 'valid';
    public const string FIELD_VALID_FOR_ORDER = 'valid_for_order';
    public const string FIELD_TYPE = 'type';
    public const string FIELD_VALUE = 'value';
    public const string FIELD_VALUE_USED = 'value_used';
    public const string FIELD_USAGE_TYPE = 'usage_type';
    public const string FIELD_COURIERS = 'couriers';
    public const string FIELD_VALID_TO = 'valid_to';
    public const string FIELD_DESCRIPTION = 'description';
    /**-@#*/

    /**
     * @param string $code the code without whitespace and symbols
     * @param bool $valid the coupon exists and this account may use it
     * @param bool $validForOrder it also applies to the order sent with the request
     * @param string|null $type `VALUE` (an amount off), `PERCENT` or `STATIC` (a fixed price)
     * @param float|null $value the discount's face value
     * @param float|null $valueUsed how much of it has already been spent
     * @param string|null $usageType `SINGLE` or `MULTI`. A `MULTI` code can be used once per
     *                               customer
     * @param string[]|null $couriers carrier codes it is restricted to, null when it is not
     *                                restricted to any carrier or does not exist
     * @param \DateTimeInterface|null $validTo expiry date, or null if it does not expire
     * @param string $description human-readable summary of the terms, in Polish
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CODE)]
        public string $code = '',

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_VALID)]
        public bool $valid = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_VALID_FOR_ORDER)]
        public bool $validForOrder = false,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        #[OmitWhenEmpty]
        public ?string $type = null,

        /**
         * @var float|null
         */
        #[Serializer\SerializedName(self::FIELD_VALUE)]
        #[OmitWhenEmpty]
        public ?float $value = null,

        /**
         * @var float|null
         */
        #[Serializer\SerializedName(self::FIELD_VALUE_USED)]
        #[OmitWhenEmpty]
        public ?float $valueUsed = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_USAGE_TYPE)]
        #[OmitWhenEmpty]
        public ?string $usageType = null,

        /**
         * @var string[]|null
         */
        #[Serializer\SerializedName(self::FIELD_COURIERS)]
        #[OmitWhenEmpty]
        public ?array $couriers = null,

        /**
         * @var \DateTimeInterface|null
         */
        #[Serializer\SerializedName(self::FIELD_VALID_TO)]
        #[OmitWhenEmpty]
        public ?\DateTimeInterface $validTo = null,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        public string $description = '',

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}
}
