<?php

namespace PolkurierWebServiceApi\Entities;

/**
 * Class PhoneNotificationRecipientCourierService
 * @package PolkurierWebServiceApi\Entities
 */
class PhoneNotificationRecipientCourierService implements CourierServiceInterface
{
    /**
     * @var bool
     */
    private $phoneNotification;

    /**
     * PhoneNotificationRecipientCourierService constructor.
     * @param bool $phoneNotification
     */
    public function __construct($phoneNotification = false)
    {
        $this->phoneNotification = $phoneNotification;
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
