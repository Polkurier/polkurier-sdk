<?php

namespace PolkurierWebServiceApi\Entities;

/**
 * Class SmsNotificationRecipientCourierService
 * @package PolkurierWebServiceApi\Entities
 */
class SmsNotificationRecipientCourierService implements CourierServiceInterface
{
    /**
     * @var bool
     */
    private $smsnotification;

    /**
     * SmsNotificationRecipientCourierService constructor.
     * @param false $smsnotification
     */
    public function __construct($smsnotification = false)
    {
        $this->smsnotification = $smsnotification;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'SMS_NOTIFICATION_RECIPIENT' => $this->smsnotification
        ];
    }

    /**
     * @return bool
     */
    public function isSmsNotificationRecipient()
    {
        return $this->smsnotification;
    }

    /**
     * @param $smsnotification
     */
    public function setSmsNotificationRecipient($smsnotification)
    {
        $this->smsnotification = $smsnotification;
    }
}
