<?php

namespace PolkurierWebServiceApi\Entities;

/**
 * Class SmsNotificationRecipientCourierService
 * @package PolkurierWebServiceApi\Entities
 */
class SmsNotificationRecipientWithNameCourierService implements CourierServiceInterface
{
    /**
     * @var bool
     */
    private $smsNotificationRecipientWithName;

    /**
     * SmsNotificationRecipientCourierService constructor.
     * @param bool $enabled
     */
    public function __construct($enabled = false)
    {
        $this->smsNotificationRecipientWithName = $enabled;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'SMS_NOTIFICATION_RECIPIENT_WITH_NAME' => $this->smsNotificationRecipientWithName,
        ];
    }

    /**
     * @return bool
     */
    public function isSmsNotificationRecipientWithName()
    {
        return $this->smsNotificationRecipientWithName;
    }

    /**
     * @param bool $smsNotificationRecipientWithName
     */
    public function setSmsNotificationRecipientWithName($smsNotificationRecipientWithName)
    {
        $this->smsNotificationRecipientWithName = $smsNotificationRecipientWithName;
    }

}
