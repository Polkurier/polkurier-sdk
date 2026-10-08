<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Carrier\AdditionalData;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * How a carrier treats one of the pickup fields of an order: the date or an end of the time window.
 *
 * The API sends 2 flags, `status` and `required`, and they mean the same thing: the carrier takes
 * this field, and an order with a booked collection needs it. Not every carrier sends both, so
 * {@see self::isStatus()} and {@see self::isRequired()} each read whichever is present.
 */
readonly class Option implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_STATUS = 'status';
    public const string FIELD_REQUIRED = 'required';
    public const string FIELD_DESCRIPTION = 'description';
    /**-@#*/

    /**
     * Every field has a default because a false value may be absent from the response (`status` in
     * particular). Absent means false. A carrier that does not offer an option may not describe it
     * at all.
     *
     * @param bool $status
     * @param bool $required
     * @param string $description
     */
    public function __construct(
        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_STATUS)]
        private bool $status = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_REQUIRED)]
        private bool $required = false,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        private string $description = '',
    ) {}

    /**
     * Whether the carrier takes this field. The same as {@see self::isRequired()}.
     *
     * @return bool
     */
    public function isStatus(): bool
    {
        return $this->status || $this->required;
    }

    /**
     * Whether an order with a booked collection needs this field. The same as
     * {@see self::isStatus()}.
     *
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required || $this->status;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}
