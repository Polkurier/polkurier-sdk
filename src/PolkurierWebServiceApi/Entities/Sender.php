<?php
namespace PolkurierWebServiceApi\Entities;

/**
 * Class Sender
 * @package PolkurierWebService\Entities
 *
 */
class Sender extends Recipient
{
    /**
     * @var bool
     */
    private $courierAccount = false;

    /**
     * @var int
     */
    private $addressBookId;

    /**
     * @return bool
     */
    public function getCourierAccount()
    {
        return $this->courierAccount;
    }

    /**
     * @param mixed $courierAccount
     * @return Sender
     */
    public function setCourierAccount($courierAccount)
    {
        $this->courierAccount = (bool)$courierAccount;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAddressBookId()
    {
        return $this->addressBookId;
    }

    /**
     * @param mixed $addressBookId
     * @return Sender
     */
    public function setAddressBookId($addressBookId)
    {
        $this->addressBookId = (int)$addressBookId;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $data = parent::toArray();
        $data['courieraccount'] = $this->getCourierAccount();
        $data['addressbookid'] = $this->getAddressBookId();
        return $data;
    }
}