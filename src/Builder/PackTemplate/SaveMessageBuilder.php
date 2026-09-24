<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\PackTemplate;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Enum\Order\AdditionalServiceEnum;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Request\PackTemplate\SaveMessage;

/**
 * Assembles a saved parcel template.
 *
 * Pass an id to update a template rather than create one.
 */
class SaveMessageBuilder extends AbstractBuilder
{
    /**
     * @param string $name required before build()
     *
     * @return static
     */
    public function name(string $name): static
    {
        return $this->set('name', $name);
    }

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
     * @param ShipmentTypeEnum $shipmentType
     *
     * @return static
     */
    public function shipmentType(ShipmentTypeEnum $shipmentType): static
    {
        return $this->set('shipmentType', $shipmentType);
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
     * @param string|null $id
     *
     * @return static
     */
    public function id(?string $id): static
    {
        return $this->set('id', $id);
    }

    /**
     * @param float $cod
     *
     * @return static
     */
    public function cod(float $cod): static
    {
        return $this->set('cod', $cod);
    }

    /**
     * @param float $insurance
     *
     * @return static
     */
    public function insurance(float $insurance): static
    {
        return $this->set('insurance', $insurance);
    }

    /**
     * @param string $description
     *
     * @return static
     */
    public function description(string $description): static
    {
        return $this->set('description', $description);
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
     * @param AdditionalServiceEnum[] $services
     *
     * @return static
     */
    public function services(array $services): static
    {
        return $this->set('services', $services);
    }

    /**
     * @return SaveMessage
     * @throws InvalidArgumentException
     */
    public function build(): SaveMessage
    {
        return $this->make(SaveMessage::class);
    }
}
