<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\MoneyBox;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Request\MoneyBox\GetMoneyBoxPayoutsMessage;

/**
 * Assembles the filters for the payout list.
 */
class GetMoneyBoxPayoutsMessageBuilder extends AbstractBuilder
{
    /**
     * @param ?string $payoutNumber
     *
     * @return static
     */
    public function payoutNumber(?string $payoutNumber): static
    {
        return $this->set('payoutNumber', $payoutNumber);
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
     * @return GetMoneyBoxPayoutsMessage
     * @throws InvalidArgumentException
     */
    public function build(): GetMoneyBoxPayoutsMessage
    {
        return $this->make(GetMoneyBoxPayoutsMessage::class);
    }
}
