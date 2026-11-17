<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Request\MoneyBox;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Dto\Request\MoneyBox\GetMoneyBoxPayoutsMessage;
use Polkurier\Webservice\Exception\InvalidArgumentException;

/**
 * Assembles the filters for the payout list.
 *
 * @api
 */
class GetMoneyBoxPayoutsMessageBuilder extends AbstractBuilder
{
    /**
     * @param string|null $payoutNumber
     *
     * @return static
     */
    public function payoutNumber(?string $payoutNumber): static
    {
        return $this->set('payoutNumber', $payoutNumber);
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
     * @return GetMoneyBoxPayoutsMessage
     * @throws InvalidArgumentException
     */
    public function build(): GetMoneyBoxPayoutsMessage
    {
        return $this->make(GetMoneyBoxPayoutsMessage::class);
    }
}
