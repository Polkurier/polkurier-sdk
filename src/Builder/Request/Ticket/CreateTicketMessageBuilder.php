<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Request\Ticket;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Dto\Request\Ticket\CreateTicketMessage;
use Polkurier\Webservice\Dto\Request\Ticket\TicketAttachmentMessage;
use Polkurier\Webservice\Enum\Ticket\TicketOriginEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;

/**
 * Assembles a support ticket.
 *
 * @api
 */
class CreateTicketMessageBuilder extends AbstractBuilder
{
    /**
     * @param string $topicId required before build()
     *
     * @return static
     */
    public function setTopicId(string $topicId): static
    {
        return $this->set('topicId', $topicId);
    }

    /**
     * @param string $content required before build()
     *
     * @return static
     */
    public function setContent(string $content): static
    {
        return $this->set('content', $content);
    }

    /**
     * @param string $name
     *
     * @return static
     */
    public function setName(string $name): static
    {
        return $this->set('name', $name);
    }

    /**
     * @param TicketOriginEnum|null $origin
     *
     * @return static
     */
    public function setOrigin(?TicketOriginEnum $origin): static
    {
        return $this->set('origin', $origin);
    }

    /**
     * @param TicketAttachmentMessage[] $files up to 5
     *
     * @return static
     */
    public function setFiles(array $files): static
    {
        return $this->set('files', $files);
    }

    /**
     * @return CreateTicketMessage
     * @throws InvalidArgumentException
     */
    public function build(): CreateTicketMessage
    {
        return $this->make(CreateTicketMessage::class);
    }
}
