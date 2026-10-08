<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Courier;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Enum\Courier\PointFunctionEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Request\Courier\GetPointMessage;

/**
 * Assembles a pickup point search.
 *
 * Only the carrier list is required. The other fields are optional filters.
 */
class GetPointMessageBuilder extends AbstractBuilder
{
    /**
     * @param string[] $couriers required before build()
     *
     * @return static
     */
    public function couriers(array $couriers): static
    {
        return $this->set('couriers', $couriers);
    }

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
     * @param PointFunctionEnum[]|null $functions
     *
     * @return static
     */
    public function functions(?array $functions): static
    {
        return $this->set('functions', $functions);
    }

    /**
     * @param int|null $limit
     *
     * @return static
     */
    public function limit(?int $limit): static
    {
        return $this->set('limit', $limit);
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
     * @return GetPointMessage
     * @throws InvalidArgumentException
     */
    public function build(): GetPointMessage
    {
        return $this->make(GetPointMessage::class);
    }
}
