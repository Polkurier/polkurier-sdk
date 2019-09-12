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
    private $machineName;

    /**
     * @return string
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param string $company
     * @return Recipient
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
     * @return Recipient
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
     * @return Recipient
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
     * @return Recipient
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
     * @return Recipient
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
     * @return Recipient
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
     * @return Recipient
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
     * @return Recipient
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
     * @param mixed $country
     * @return Recipient
     */
    public function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }

    /**
     * @return string
     */
    public function getMachineName()
    {
        return $this->machineName;
    }

    /**
     * @param string $machineName
     * @return Recipient
     */
    public function setMachineName($machineName)
    {
        $this->machineName = $machineName;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'company'       => $this->getCompany(),
            'person'        => $this->getPerson(),
            'street'        => $this->getStreet(),
            'housenumber'   => $this->getHouseNumber(),
            'flatnumber'    => $this->getFlatNumber(),
            'postcode'      => $this->getPostcode(),
            'city'          => $this->getCity(),
            'email'         => $this->getEmail(),
            'phone'         => $this->getPhone(),
            'country'       => $this->getCountry(),
            'machinename'   => $this->getMachineName(),
        ];
    }
}