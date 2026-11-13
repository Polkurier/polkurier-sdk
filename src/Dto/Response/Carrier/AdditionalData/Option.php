<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Carrier\AdditionalData;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * How a carrier treats one of the pickup fields of an order: the date or an end of the time window.
 *
 * The API sends 2 flags, `status` and `required`, each read as sent. A flag the carrier leaves out is
 * false.
 *
 * @api
 */
final readonly class Option implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_STATUS = 'status';
    public const string FIELD_REQUIRED = 'required';
    public const string FIELD_DESCRIPTION = 'description';
    /**-@#*/

    /**
     * @param bool $status
     * @param bool $required
     * @param string $description
     */
    public function __construct(
        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_STATUS)]
        public bool $status = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_REQUIRED)]
        public bool $required = false,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        public string $description = '',
    ) {}
}
