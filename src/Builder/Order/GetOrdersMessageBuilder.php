<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Order;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Enum\Order\OrderStatusCodeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Request\Order\GetOrdersMessage;

/**
 * Assembles the filters for the order list.
 *
 * Every filter is optional. Set only the ones you need.
 */
class GetOrdersMessageBuilder extends AbstractBuilder
{
    /**
     * @param string|null $orderNo
     *
     * @return static
     */
    public function orderNo(?string $orderNo): static
    {
        return $this->set('orderNo', $orderNo);
    }

    /**
     * @param OrderStatusCodeEnum|null $status
     *
     * @return static
     */
    public function status(?OrderStatusCodeEnum $status): static
    {
        return $this->set('status', $status);
    }

    /**
     * @param string|null $courier
     *
     * @return static
     */
    public function courier(?string $courier): static
    {
        return $this->set('courier', $courier);
    }

    /**
     * @param float|null $codMin
     *
     * @return static
     */
    public function codMin(?float $codMin): static
    {
        return $this->set('codMin', $codMin);
    }

    /**
     * @param float|null $codMax
     *
     * @return static
     */
    public function codMax(?float $codMax): static
    {
        return $this->set('codMax', $codMax);
    }

    /**
     * @param \DateTimeInterface|null $dateFrom
     *
     * @return static
     */
    public function dateFrom(?\DateTimeInterface $dateFrom): static
    {
        return $this->set('dateFrom', $dateFrom);
    }

    /**
     * @param \DateTimeInterface|null $dateTo
     *
     * @return static
     */
    public function dateTo(?\DateTimeInterface $dateTo): static
    {
        return $this->set('dateTo', $dateTo);
    }

    /**
     * @param string|null $searchPhrase
     *
     * @return static
     */
    public function searchPhrase(?string $searchPhrase): static
    {
        return $this->set('searchPhrase', $searchPhrase);
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
     * Lists only orders placed with this application id, exact match.
     *
     * @param string|null $externalApplicationId
     *
     * @return static
     */
    public function externalApplicationId(?string $externalApplicationId): static
    {
        return $this->set('externalApplicationId', $externalApplicationId);
    }

    /**
     * Lists only the order with this id of yours, exact match. Needs externalApplicationId() as
     * well, and the SDK refuses it without one.
     *
     * @param string|null $externalOrderId
     *
     * @return static
     */
    public function externalOrderId(?string $externalOrderId): static
    {
        return $this->set('externalOrderId', $externalOrderId);
    }

    /**
     * @param bool|null $packs
     *
     * @return static
     */
    public function packs(?bool $packs): static
    {
        return $this->set('packs', $packs);
    }

    /**
     * @param bool|null $items
     *
     * @return static
     */
    public function items(?bool $items): static
    {
        return $this->set('items', $items);
    }

    /**
     * @param bool|null $files
     *
     * @return static
     */
    public function files(?bool $files): static
    {
        return $this->set('files', $files);
    }

    /**
     * @return GetOrdersMessage
     * @throws InvalidArgumentException
     */
    public function build(): GetOrdersMessage
    {
        return $this->make(GetOrdersMessage::class);
    }
}
