<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Ticket;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Request\Ticket\GetTicketsMessage;

/**
 * Assembles the filters for the ticket list.
 */
class GetTicketsMessageBuilder extends AbstractBuilder
{
    /**
     * @param ?string $id
     *
     * @return static
     */
    public function id(?string $id): static
    {
        return $this->set('id', $id);
    }

    /**
     * @param ?string $searchQuery
     *
     * @return static
     */
    public function searchQuery(?string $searchQuery): static
    {
        return $this->set('searchQuery', $searchQuery);
    }

    /**
     * @param ?int $pageSize
     *
     * @return static
     */
    public function pageSize(?int $pageSize): static
    {
        return $this->set('pageSize', $pageSize);
    }

    /**
     * @param ?int $page
     *
     * @return static
     */
    public function page(?int $page): static
    {
        return $this->set('page', $page);
    }

    /**
     * @return GetTicketsMessage
     * @throws InvalidArgumentException
     */
    public function build(): GetTicketsMessage
    {
        return $this->make(GetTicketsMessage::class);
    }
}
