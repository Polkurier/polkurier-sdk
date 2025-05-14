<?php

namespace PolkurierWebServiceApi\Entities;

class CourierService implements CourierServiceInterface
{

    /**
     * @var string
     */
    private $serviceId;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @param string $serviceId
     * @param bool $enabled
     */
    public function __construct($serviceId, $enabled = false)
    {
        $this->serviceId = (string)$serviceId;
        $this->enabled = (bool)$enabled;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            $this->serviceId => $this->enabled,
        ];
    }

    /**
     * @return string
     */
    public function getServiceId()
    {
        return $this->serviceId;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     * @return CourierService $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool)$enabled;
        return $this;
    }

}
