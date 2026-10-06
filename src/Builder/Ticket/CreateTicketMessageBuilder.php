<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Ticket;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Enum\Ticket\TicketOriginEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Request\Ticket\CreateTicketMessage;
use Polkurier\Webservice\Request\Ticket\TicketAttachmentMessage;

/**
 * Assembles a support ticket.
 */
class CreateTicketMessageBuilder extends AbstractBuilder
{
    /**
     * @param string $topicId required before build()
     *
     * @return static
     */
    public function topicId(string $topicId): static
    {
        return $this->set('topicId', $topicId);
    }

    /**
     * @param string $content required before build()
     *
     * @return static
     */
    public function content(string $content): static
    {
        return $this->set('content', $content);
    }

    /**
     * @param string $name
     *
     * @return static
     */
    public function name(string $name): static
    {
        return $this->set('name', $name);
    }

    /**
     * @param ?TicketOriginEnum $origin
     *
     * @return static
     */
    public function origin(?TicketOriginEnum $origin): static
    {
        return $this->set('origin', $origin);
    }

    /**
     * @param TicketAttachmentMessage[] $files up to 5
     *
     * @return static
     */
    public function files(array $files): static
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
