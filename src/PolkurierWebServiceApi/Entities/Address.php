<?php

namespace PolkurierWebServiceApi\Entities;

/**
 * Class Address
 * @package PolkurierWebServiceApi\Entities
 */
class Address
{
    private $id;
    private $name;
    private $company;
    private $person;
    private $city;
    private $postcode;
    private $street;
    private $houseNumber;
    private $flatNumber;
    private $email;
    private $phone;
    private $country;
    private $province;
    private $type;
    private $default;
    private $isCoverAddress;

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setCompany($company)
    {
        $this->company = $company;
        return $this;
    }

    public function getCompany()
    {
        return $this->company;
    }

    public function setPerson($person)
    {
        $this->person = $person;
        return $this;
    }

    public function getPerson()
    {
        return $this->person;
    }

    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function setPostcode($postcode)
    {
        $this->postcode = $postcode;
        return $this;
    }

    public function getPostcode()
    {
        return $this->postcode;
    }

    public function setStreet($street)
    {
        $this->street = $street;
        return $this;
    }

    public function getStreet()
    {
        return $this->street;
    }

    public function setHouseNumber($houseNumber)
    {
        $this->houseNumber = $houseNumber;
        return $this;
    }

    public function getHouseNumber()
    {
        return $this->houseNumber;
    }

    public function setFlatNumber($flatNumber)
    {
        $this->flatNumber = $flatNumber;
        return $this;
    }

    public function getFlatNumber()
    {
        return $this->flatNumber;
    }

    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function setProvince($province)
    {
        $this->province = $province;
        return $this;
    }

    public function getProvince()
    {
        return $this->province;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setDefault($default)
    {
        $this->default = (bool)$default;
        return $this;
    }

    public function getDefault()
    {
        return $this->default;
    }

    public function setIsCoverAddress($isCoverAddress)
    {
        $this->isCoverAddress = (bool)$isCoverAddress;
        return $this;
    }

    public function getIsCoverAddress()
    {
        return $this->isCoverAddress;
    }

    public function toArray()
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
            'is_cover_address' => $this->isCoverAddress,
        ];
    }
}
