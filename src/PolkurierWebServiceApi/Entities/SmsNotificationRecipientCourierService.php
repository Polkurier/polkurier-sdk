<?php

namespace PolkurierWebServiceApi\Entities;

class SmsNotificationRecipientCourierService implements CourierServiceInterface
{

    /**
     * @var bool
     */
    private $smsnotification;

    /**
     * @param bool $smsnotification
     */
    public function __construct($smsnotification = false)
    {
        $this->smsnotification = (bool)$smsnotification;
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
     * @param bool $smsnotification
     */
    public function setSmsNotificationRecipient($smsnotification)
    {
        $this->smsnotification = (bool)$smsnotification;
    }

}
