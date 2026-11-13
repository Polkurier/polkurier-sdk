<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Pack;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Entity\Pack\BasePackMessage;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;

/**
 * The setters for the dimensions, weight and type shared by every parcel payload.
 *
 * Extended by the builders of the messages that carry a parcel.
 */
abstract class BasePackMessageBuilder extends AbstractBuilder
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
     * @return BasePackMessage
     * @throws InvalidArgumentException
     */
    abstract public function build(): BasePackMessage;
}
