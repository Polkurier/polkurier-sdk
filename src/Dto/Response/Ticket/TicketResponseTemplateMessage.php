<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Ticket;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A question in Polkurier's latest reply that takes one of a fixed set of answers.
 *
 * A reply must be one of the answers; anything else is a VALIDATION_ERROR on `content`.
 */
final readonly class TicketResponseTemplateMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_MESSAGE = 'message';
    public const string FIELD_RESPONSES = 'responses';
    /**-@#*/

    /**
     * @param string $message the reply that asks the question
     * @param string[] $responses the answers a reply can give
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_MESSAGE)]
        private string $message = '',

        /**
         * @var string[]
         */
        #[Serializer\SerializedName(self::FIELD_RESPONSES)]
        private array $responses = [],
    ) {}

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return string[]
     */
    public function getResponses(): array
    {
        return $this->responses;
    }
}
