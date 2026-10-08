<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\System;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The verdict on a discount coupon.
 *
 * {@see self::isValid()} says the coupon exists and the account may use it.
 * {@see self::isValidForOrder()} says it also applies to the order sent with the request. When no
 * order was sent, the second is always false. That means there was no order, not that a check
 * failed.
 *
 * For a code that does not exist, every descriptive field is null, so check validity before reading
 * them.
 */
readonly class CouponValidationMessage implements MessageInterface
{
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
     * @param string|null $type discount type, e.g. a fixed amount or a percentage
     * @param float|null $value the discount's face value
     * @param float|null $valueUsed how much of it has already been spent
     * @param string|null $usageType whether it may be used once or repeatedly
     * @param string[]|null $couriers carrier codes it is restricted to. Empty means any carrier,
     *                                null means the coupon does not exist
     * @param \DateTimeInterface|null $validTo expiry date, or null if it does not expire
     * @param string $description human-readable summary of the terms, in Polish
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CODE)]
        private string $code = '',

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_VALID)]
        private bool $valid = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_VALID_FOR_ORDER)]
        private bool $validForOrder = false,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        #[OmitWhenEmpty]
        private ?string $type = null,

        /**
         * @var float|null
         */
        #[Serializer\SerializedName(self::FIELD_VALUE)]
        #[OmitWhenEmpty]
        private ?float $value = null,

        /**
         * @var float|null
         */
        #[Serializer\SerializedName(self::FIELD_VALUE_USED)]
        #[OmitWhenEmpty]
        private ?float $valueUsed = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_USAGE_TYPE)]
        #[OmitWhenEmpty]
        private ?string $usageType = null,

        /**
         * Null, not an empty list, when the coupon does not exist. Check it before iterating.
         *
         * @var string[]|null
         */
        #[Serializer\SerializedName(self::FIELD_COURIERS)]
        #[OmitWhenEmpty]
        private ?array $couriers = null,

        /**
         * @var \DateTimeInterface|null
         */
        #[Serializer\SerializedName(self::FIELD_VALID_TO)]
        #[OmitWhenEmpty]
        private ?\DateTimeInterface $validTo = null,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        private string $description = '',
    ) {}

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * @return bool
     */
    public function isValidForOrder(): bool
    {
        return $this->validForOrder;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return float|null
     */
    public function getValue(): ?float
    {
        return $this->value;
    }

    /**
     * @return float|null
     */
    public function getValueUsed(): ?float
    {
        return $this->valueUsed;
    }

    /**
     * @return string|null
     */
    public function getUsageType(): ?string
    {
        return $this->usageType;
    }

    /**
     * @return string[]|null
     */
    public function getCouriers(): ?array
    {
        return $this->couriers;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getValidTo(): ?\DateTimeInterface
    {
        return $this->validTo;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}
