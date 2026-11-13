<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Entity;

use Polkurier\Webservice\Entity\PackTemplate;
use Polkurier\Webservice\Enum\Order\AdditionalServiceEnum;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;

/**
 * Assembles a saved parcel template.
 *
 * Set an id to update an existing template. Without one, a new template is created.
 *
 * @api
 */
class PackTemplateBuilder extends PackBuilder
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
     * @param ShipmentTypeEnum $shipmentType
     *
     * @return static
     */
    public function shipmentType(ShipmentTypeEnum $shipmentType): static
    {
        return $this->set('shipmentType', $shipmentType);
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
     * @return PackTemplate
     * @throws InvalidArgumentException
     */
    public function build(): PackTemplate
    {
        return $this->make(PackTemplate::class);
    }
}
