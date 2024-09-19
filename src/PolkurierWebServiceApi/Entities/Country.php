<?php

namespace PolkurierWebServiceApi\Entities;

/**
 * Class Country
 * @package PolkurierWebServiceApi\Entities
 */
class Country
{
    private $id;
    private $name;
    private $isUe;
    private $isDefault;
    private $provinces = [];

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setIsUe($isUe)
    {
        $this->isUe = (bool)$isUe;
        return $this;
    }

    public function getIsUe()
    {
        return $this->isUe;
    }

    public function setIsDefault($isDefault)
    {
        $this->isDefault = (bool)$isDefault;
        return $this;
    }

    public function getIsDefault()
    {
        return $this->isDefault;
    }

    public function setProvinces(array $provinces)
    {
        $this->provinces = $provinces;
        return $this;
    }

    public function getProvinces()
    {
        return $this->provinces;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_ue' => $this->isUe,
            'is_default' => $this->isDefault,
            'provinces' => $this->provinces,
        ];
    }
}
