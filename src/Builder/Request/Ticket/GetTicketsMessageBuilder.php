<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Request\Ticket;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Dto\Request\Ticket\GetTicketsMessage;
use Polkurier\Webservice\Exception\InvalidArgumentException;

/**
 * Assembles the filters for the ticket list.
 *
 * @api
 */
class GetTicketsMessageBuilder extends AbstractBuilder
{
    /**
     * @param string|null $id
     *
     * @return static
     */
    public function id(?string $id): static
    {
        return $this->set('id', $id);
    }

    /**
     * @param string|null $searchQuery
     *
     * @return static
     */
    public function searchQuery(?string $searchQuery): static
    {
        return $this->set('searchQuery', $searchQuery);
    }

    /**
     * @param int|null $pageSize
     *
     * @return static
     */
    public function pageSize(?int $pageSize): static
    {
        return $this->set('pageSize', $pageSize);
    }

    /**
     * @param int|null $page
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
