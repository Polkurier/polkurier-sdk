<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Order;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Request\Order\PackMessage;

/**
 * Assembles one parcel on an order.
 */
class PackMessageBuilder extends AbstractBuilder
{
    /**
     * @param int $length required before build()
     *
     * @return static
     */
    public function length(int $length): static
    {
        return $this->set('length', $length);
    }

    /**
     * @param int $width required before build()
     *
     * @return static
     */
    public function width(int $width): static
    {
        return $this->set('width', $width);
    }

    /**
     * @param int $height required before build()
     *
     * @return static
     */
    public function height(int $height): static
    {
        return $this->set('height', $height);
    }

    /**
     * @param float $weight required before build()
     *
     * @return static
     */
    public function weight(float $weight): static
    {
        return $this->set('weight', $weight);
    }

    /**
     * @param PackTypeEnum $type
     *
     * @return static
     */
    public function type(PackTypeEnum $type): static
    {
        return $this->set('type', $type);
    }

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
