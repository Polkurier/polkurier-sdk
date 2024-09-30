<?php

namespace PolkurierWebServiceApi\Entities;

use JsonSerializable;

class Address implements JsonSerializable
{

    /**
     * @var string
     */
    private $id = '';

    /**
     * @var string
     */
    private $name = '';

    /**
     * @var string
     */
    private $company = '';

    /**
     * @var string
     */
    private $person = '';

    /**
     * @var string
     */
    private $city = '';

    /**
     * @var string
     */
    private $postcode = '';

    /**
     * @var string
     */
    private $street = '';

    /**
     * @var string
     */
    private $houseNumber = '';

    /**
     * @var string
     */
    private $flatNumber = '';

    /**
     * @var string
     */
    private $email = '';

    /**
     * @var string
     */
    private $phone = '';

    /**
     * @var string
     */
    private $country = '';

    /**
     * @var string
     */
    private $province = '';

    /**
     * @var string
     */
    private $type = '';

    /**
     * @var bool
     */
    private $default = false;

    /**
     * @var bool
     */
    private $coverAddress = false;

    /**
     * @param string $id
     * @return Address
     */
    public function setId($id)
    {
        $this->id = (string)$id;
        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     * @return Address
     */
    public function setName($name)
    {
        $this->name = (string)$name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $company
     * @return Address
     */
    public function setCompany($company)
    {
        $this->company = (string)$company;
        return $this;
    }

    /**
     * @return string
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param string $person
     * @return Address
     */
    public function setPerson($person)
    {
        $this->person = (string)$person;
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
     * @param string $city
     * @return Address
     */
    public function setCity($city)
    {
        $this->city = (string)$city;
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
     * @param string $postcode
     * @return Address
     */
    public function setPostcode($postcode)
    {
        $this->postcode = (string)$postcode;
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
     * @param string $street
     * @return Address
     */
    public function setStreet($street)
    {
        $this->street = (string)$street;
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
     * @param string $houseNumber
     * @return Address
     */
    public function setHouseNumber($houseNumber)
    {
        $this->houseNumber = (string)$houseNumber;
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
     * @param string|null $flatNumber
     * @return Address
     */
    public function setFlatNumber($flatNumber)
    {
        $this->flatNumber = (string)$flatNumber;
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
     * @param string $email
     * @return Address
     */
    public function setEmail($email)
    {
        $this->email = (string)$email;
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
     * @param string $phone
     * @return Address
     */
    public function setPhone($phone)
    {
        $this->phone = (string)$phone;
        return $this;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string $country
     * @return Address
     */
    public function setCountry($country)
    {
        $this->country = (string)$country;
        return $this;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $province
     * @return Address
     */
    public function setProvince($province)
    {
        $this->province = (string)$province;
        return $this;
    }

    /**
     * @return string
     */
    public function getProvince()
    {
        return $this->province;
    }

    /**
     * @param string $type
     * @return Address
     */
    public function setType($type)
    {
        $this->type = (string)$type;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param bool $default
     * @return Address
     */
    public function setDefault($default)
    {
        $this->default = (bool)$default;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDefault()
    {
        return $this->default;
    }

    /**
     * @param bool $coverAddress
     * @return Address
     */
    public function setCoverAddress($coverAddress)
    {
        $this->coverAddress = (bool)$coverAddress;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCoverAddress()
    {
        return $this->coverAddress;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'company' => $this->company,
            'person' => $this->person,
            'city' => $this->city,
            'postcode' => $this->postcode,
            'street' => $this->street,
            'housenumber' => $this->houseNumber,
            'flatnumber' => $this->flatNumber,
            'email' => $this->email,
            'phone' => $this->phone,
            'country' => $this->country,
            'province' => $this->province,
            'type' => $this->type,
            'default' => $this->default,
            'is_cover_address' => $this->coverAddress,
        ];
    }

}
