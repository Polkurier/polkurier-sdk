<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Entity\Order;

use Polkurier\Webservice\Builder\Entity\PackBuilder;
use Polkurier\Webservice\Entity\Order\PackItem;
use Polkurier\Webservice\Exception\InvalidArgumentException;

/**
 * Assembles one parcel on an order.
 *
 * @api
 */
class PackItemBuilder extends PackBuilder
{
    /**
     * @param int $amount
     *
     * @return static
     */
    public function setAmount(int $amount): static
    {
        return $this->set('amount', $amount);
    }

    /**
     * @return PackItem
     * @throws InvalidArgumentException
     */
    public function build(): PackItem
    {
        return $this->make(PackItem::class);
    }
}
