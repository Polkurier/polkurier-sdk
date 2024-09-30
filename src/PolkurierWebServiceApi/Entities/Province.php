<?php

namespace PolkurierWebServiceApi\Entities;

use JsonSerializable;

class Province implements JsonSerializable
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
     * @param string $id
     * @param string $name
     */
    public function __construct($id, $name)
    {
        $this->id = (string)$id;
        $this->name = (string)$name;
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
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }

}
