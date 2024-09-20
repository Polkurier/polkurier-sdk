<?php

namespace PolkurierWebServiceApi\Entities;

class Country implements \JsonSerializable
{
    /**
     * @var string|null
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $isUe;

    /**
     * @var bool
     */
    private $isDefault;

    /**
     * @var Province[]
     */
    private $provinces;

    /**
     * @param string|null $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param bool $isUe
     * @return $this
     */
    public function setIsUe($isUe)
    {
        $this->isUe = (bool)$isUe;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsUe()
    {
        return $this->isUe;
    }

    /**
     * @param bool $isDefault
     * @return $this
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = (bool)$isDefault;
        return $this;
    }

    /**
     * @return bool mixed
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    /**
     * @param Province[] $provinces
     * @return $this
     */
    public function setProvinces(array $provinces)
    {
        $this->provinces = $provinces;
        return $this;
    }

    /**
     * @return Province[]
     */
    public function getProvinces()
    {
        return $this->provinces;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
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
