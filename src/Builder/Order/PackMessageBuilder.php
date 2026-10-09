<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Order;

use Polkurier\Webservice\Builder\Common\BasePackMessageBuilder;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Request\Order\PackMessage;

/**
 * Assembles one parcel on an order.
 */
class PackMessageBuilder extends BasePackMessageBuilder
{
    /**
     * @param int $amount
     *
     * @return static
     */
    public function amount(int $amount): static
    {
        return $this->set('amount', $amount);
    }

    /**
     * @return PackMessage
     * @throws InvalidArgumentException
     */
    public function build(): PackMessage
    {
        return $this->make(PackMessage::class);
    }
}
