<?php

namespace PolkurierWebServiceApi\Entities;

class PhoneNotificationRecipientCourierService implements CourierServiceInterface
{

    /**
     * @var bool
     */
    private $phoneNotification;

    /**
     * @param bool $phoneNotification
     */
    public function __construct($phoneNotification = false)
    {
        $this->phoneNotification = (bool)$phoneNotification;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'PHONE_NOTIFICATION_RECIPIENT' => $this->phoneNotification
        ];
    }

    /**
     * @return bool
     */
    public function isPhoneNotification()
    {
        return $this->phoneNotification;
    }

    /**
     * @param bool $phoneNotification
     */
    public function setPhoneNotification($phoneNotification)
    {
        $this->phoneNotification = (bool)$phoneNotification;
    }

}
