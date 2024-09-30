<?php

namespace PolkurierWebServiceApi\Entities;

class CourierMessage
{

    /**
     * @var string
     */
    private $courier;

    /**
     * @var string
     */
    private $message;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $severity;

    /**
     * @var int
     */
    private $hide_timeout;

    /**
     * @param string $courier
     * @param string $message
     * @param string $type
     * @param string $severity
     * @param int $hide_timeout
     */
    public function __construct(
        $courier = '',
        $message = '',
        $type = '',
        $severity = '',
        $hide_timeout = 0
    )
    {
        $this->courier = (string)$courier;
        $this->message = (string)$message;
        $this->type = (string)$type;
        $this->severity = (string)$severity;
        $this->hide_timeout = (int)$hide_timeout;
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
