<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
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
 * Worth surfacing before a customer picks a carrier — an alert here usually means orders placed
 * now will be affected. The text is Polish and ready to display.
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
    /**-@#*/

    /**
     * @param string $courier carrier code the notice concerns
     * @param string $message the text to display, in Polish
     * @param CourierMessageTypeEnum $type
     * @param CourierMessageSeverityEnum $severity
     * @param int $hideTimeout seconds the notice should stay on screen; 0 means leave it up
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
         * @var CourierMessageTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        private CourierMessageTypeEnum $type = CourierMessageTypeEnum::Message,

        /**
         * @var CourierMessageSeverityEnum
         */
        #[Serializer\SerializedName(self::FIELD_SEVERITY)]
        private CourierMessageSeverityEnum $severity = CourierMessageSeverityEnum::Warning,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_HIDE_TIMEOUT)]
        private int $hideTimeout = 0,
    ) {}

    /**
     * @return string
     */
    public function getCourier(): string
    {
        return $this->courier;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return CourierMessageTypeEnum
     */
    public function getType(): CourierMessageTypeEnum
    {
        return $this->type;
    }

    /**
     * @return CourierMessageSeverityEnum
     */
    public function getSeverity(): CourierMessageSeverityEnum
    {
        return $this->severity;
    }

    /**
     * @return int
     */
    public function getHideTimeout(): int
    {
        return $this->hideTimeout;
    }
}
