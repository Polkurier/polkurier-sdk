<?php

namespace PolkurierWebServiceApi\Entities;

use PolkurierWebServiceApi\Util\Arr;

class Country implements \JsonSerializable
{

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $ue;

    /**
     * @var bool
     */
    private $default;

    /**
     * @var Province[]
     */
    private $provinces;

    public function __construct(
        $id = '',
        $name = '',
        $isUe = false,
        $isDefault = false,
        array $provinces = []
    )
    {
        Arr::assertInstancesOf($provinces, Province::class);
        $this->id = (string)$id;
        $this->name = (string)$name;
        $this->ue = (bool)$isUe;
        $this->default = (bool)$isDefault;
        $this->provinces = $provinces;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isUe()
    {
        return $this->ue;
    }

    /**
     * @return bool
     */
    public function isDefault()
    {
        return $this->default;
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
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_ue' => $this->ue,
            'is_default' => $this->default,
            'provinces' => array_map(static function (Province $province) {
                return $province->jsonSerialize();
            }, $this->provinces),
        ];
    }

}
