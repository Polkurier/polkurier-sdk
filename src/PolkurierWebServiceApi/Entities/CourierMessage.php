<?php

namespace PolkurierWebServiceApi\Entities;

class CourierMessage
{

    private $courier;
    private $message;
    private $type;
    private $severity;
    private $hide_timeout;

    public function __construct(
        $courier = '',
        $message = '',
        $type = '',
        $severity = '',
        $hide_timeout = 0
    )
    {
        $this->courier = $courier;
        $this->message = $message;
        $this->type = $type;
        $this->severity = $severity;
        $this->hide_timeout = $hide_timeout;
    }

    /**
     * @return string
     */
    public function getCourier()
    {
        return $this->courier;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getSeverity()
    {
        return $this->severity;
    }

    /**
     * @return int
     */
    public function getHideTimeout()
    {
        return $this->hide_timeout;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'courier' => $this->courier,
            'message' => $this->message,
            'type' => $this->type,
            'severity' => $this->severity,
            'hide_timeout' => $this->hide_timeout,
        ];
    }

}
