<?php

namespace PolkurierWebServiceApi\Entities;
/**
 * Class Recipient
 * @package PolkurierWebServiceApi\Entities
 *
 */
class Recipient
{
    /**
     * @var string
     */
    private $company;
    /**
     * @var string
     */
    private $person;
    /**
     * @var string
     */
    private $street;
    /**
     * @var string
     */
    private $houseNumber;
    /**
     * @var string
     */
    private $flatNumber;
    /**
     * @var string
     */
    private $postcode;
    /**
     * @var string
     */
    private $city;
    /**
     * @var string
     */
    private $email;
    /**
     * @var int
     */
    private $phone;
    /**
     * @var
     */
    private $country = 'PL';
    /**
     * @var string
     */
    private $point_id;

    /**
     * @return string
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param string $company
     * @return $this
     */
    public function setCompany($company)
    {
        $this->company = $company;
        return $this;
    }

    /**
     * @return string
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * @param string $person
     * @return $this
     */
    public function setPerson($person)
    {
        $this->person = $person;
        return $this;
    }

    /**
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @param string $street
     * @return $this
     */
    public function setStreet($street)
    {
        $this->street = $street;
        return $this;
    }

    /**
     * @return string
     */
    public function getHouseNumber()
    {
        return $this->houseNumber;
    }

    /**
     * @param string $houseNumber
     * @return $this
     */
    public function setHouseNumber($houseNumber)
    {
        $this->houseNumber = $houseNumber;
        return $this;
    }

    /**
     * @return string
     */
    public function getFlatNumber()
    {
        return $this->flatNumber;
    }

    /**
     * @param string $flatNumber
     * @return $this
     */
    public function setFlatNumber($flatNumber)
    {
        $this->flatNumber = $flatNumber;
        return $this;
    }

    /**
     * @return string
     */
    public function getPostcode()
    {
        return $this->postcode;
    }

    /**
     * @param string $postcode
     * @return $this
     */
    public function setPostcode($postcode)
    {
        $this->postcode = $postcode;
        return $this;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     * @return Recipient
     */
    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return int
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param int $phone
     * @return $this
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param $country
     * @return $this
     */
    public function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }

    /**
     * @deprecated
     * @return string
     */
    public function getMachineName()
    {
        return $this->point_id;
    }

    /**
     * @deprecated
     * @param string $machineName
     * @return $this
     */
    public function setMachineName($machineName)
    {
        $this->point_id = $machineName;
        return $this;
    }

    /**
     * @return string
     */
    public function getPointId()
    {
        return $this->point_id;
    }

    /**
     * @param string $point_id
     */
    public function setPointId($point_id)
    {
        $this->point_id = $point_id;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'company' => $this->getCompany(),
            'person' => $this->getPerson(),
            'street' => $this->getStreet(),
            'housenumber' => $this->getHouseNumber(),
            'flatnumber' => $this->getFlatNumber(),
            'postcode' => $this->getPostcode(),
            'city' => $this->getCity(),
            'email' => $this->getEmail(),
            'phone' => $this->getPhone(),
            'country' => $this->getCountry(),
            'point_id' => $this->getPointId(),
        ];
    }
}