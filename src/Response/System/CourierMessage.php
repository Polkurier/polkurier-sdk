<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\System;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Courier\CourierMessageSeverityEnum;
use Polkurier\Webservice\Enum\Courier\CourierMessageTypeEnum;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A notice about one carrier: a delay, a suspension, or general information.
 *
 * Show these before a customer picks a carrier. An alert usually means orders placed now will be
 * affected. The text is in Polish and ready to display.
 */
readonly class CourierMessage implements MessageInterface
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
     * @param string $message the text to display, in Polish
     * @param CourierMessageTypeEnum|null $type
     * @param CourierMessageSeverityEnum|null $severity
     * @param int $hideTimeout seconds the notice should stay on screen, 0 to leave it up; sent with
     *        alerts only
     * @param string $title short title of the notice; sent with messages only
     * @param int $priority rank of the notice, higher first; sent with messages only
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_COURIER)]
        private string $courier = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_MESSAGE)]
        private string $message = '',

        /**
         * @var CourierMessageTypeEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        private ?CourierMessageTypeEnum $type = CourierMessageTypeEnum::Message,

        /**
         * @var CourierMessageSeverityEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_SEVERITY)]
        private ?CourierMessageSeverityEnum $severity = CourierMessageSeverityEnum::Warning,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_HIDE_TIMEOUT)]
        private int $hideTimeout = 0,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TITLE)]
        private string $title = '',

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_PRIORITY)]
        private int $priority = 0,
    ) {}

    /**
     * @return string
     */
    public function getCourier(): string
    {
        return $this->courier;
    }

    /**
     * The notice as HTML, with tags such as `<p>`, `<strong>` and `<a>`. Sanitise it before you
     * display it, or strip the tags for plain text.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return CourierMessageTypeEnum|null null for a value this version of the SDK doesn't know
     */
    public function getType(): ?CourierMessageTypeEnum
    {
        return $this->type;
    }

    /**
     * @return CourierMessageSeverityEnum|null null for a value this version of the SDK doesn't know
     */
    public function getSeverity(): ?CourierMessageSeverityEnum
    {
        return $this->severity;
    }

    /**
     * @return int 0 for a notice of type MESSAGE, which has no timeout
     */
    public function getHideTimeout(): int
    {
        return $this->hideTimeout;
    }

    /**
     * @return string empty for a notice of type ALERT, which has no title
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Messages are listed by descending priority.
     *
     * @return int 0 for a notice of type ALERT, which has no priority
     */
    public function getPriority(): int
    {
        return $this->priority;
    }
}
