<?php

namespace PolkurierWebServiceApi\Entities;

class Country
{
    /**
     * @var
     */
    private $id;
    /**
     * @var
     */
    private $name;
    /**
     * @var
     */
    private $isUe;
    /**
     * @var
     */
    private $isDefault;
    /**
     * @var array
     */
    private $provinces = [];

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $isUe
     * @return $this
     */
    public function setIsUe($isUe)
    {
        $this->isUe = (bool)$isUe;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsUe()
    {
        return $this->isUe;
    }

    /**
     * @param $isDefault
     * @return $this
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = (bool)$isDefault;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    /**
     * @param array $provinces
     * @return $this
     */
    public function setProvinces(array $provinces)
    {
        $this->provinces = $provinces;
        return $this;
    }

    /**
     * @return array
     */
    public function getProvinces()
    {
        return $this->provinces;
    }

    /**
     * @return array
     */
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
