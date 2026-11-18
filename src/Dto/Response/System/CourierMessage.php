<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\System;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Courier\CourierMessageSeverityEnum;
use Polkurier\Webservice\Enum\Courier\CourierMessageTypeEnum;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A notice about one carrier: a delay, a suspension, or general information.
 *
 * Show these before a customer picks a carrier. An alert usually means orders placed now will be
 * affected.
 *
 * @api
 */
final readonly class CourierMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_COURIER = 'courier';
    public const string FIELD_MESSAGE = 'message';
    public const string FIELD_TYPE = 'type';
    public const string FIELD_SEVERITY = 'severity';
    public const string FIELD_HIDE_TIMEOUT = 'hide_timeout';
    public const string FIELD_TITLE = 'title';
    public const string FIELD_PRIORITY = 'priority';
    /**-@#*/

    /**
     * @param string $courier carrier code the notice concerns
     * @param string $message the notice, as HTML in Polish
     * @param CourierMessageTypeEnum|null $type
     * @param CourierMessageSeverityEnum|null $severity
     * @param int $hideTimeout seconds the notice should stay on screen, 0 to leave it up; sent with
     *        alerts only
     * @param string $title short title of the notice; sent with messages only
     * @param int $priority rank of the notice, higher first; sent with messages only
     */
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_COURIER)]
        public string $courier = '',

        /**
         * The notice as HTML, with tags such as `<p>`, `<strong>` and `<a>`. Sanitise it before you
         * display it, or strip the tags for plain text.
         */
        #[Serializer\SerializedName(self::FIELD_MESSAGE)]
        public string $message = '',

        /**
         * @var CourierMessageTypeEnum|null null for a value the SDK doesn't know
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        public ?CourierMessageTypeEnum $type = null,

        /**
         * @var CourierMessageSeverityEnum|null null for a value the SDK doesn't know
         */
        #[Serializer\SerializedName(self::FIELD_SEVERITY)]
        public ?CourierMessageSeverityEnum $severity = null,

        /**
         * @var int 0 for a notice of type MESSAGE, which has no timeout
         */
        #[Serializer\SerializedName(self::FIELD_HIDE_TIMEOUT)]
        public int $hideTimeout = 0,

        /**
         * @var string empty for a notice of type ALERT, which has no title
         */
        #[Serializer\SerializedName(self::FIELD_TITLE)]
        public string $title = '',

        /**
         * Messages are listed by descending priority.
         *
         * @var int 0 for a notice of type ALERT, which has no priority
         */
        #[Serializer\SerializedName(self::FIELD_PRIORITY)]
        public int $priority = 0,
    ) {}
}
