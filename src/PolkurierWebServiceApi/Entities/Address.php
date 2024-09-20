<?php

namespace PolkurierWebServiceApi\Entities;

/**
 * Class Address
 * @package PolkurierWebServiceApi\Entities
 */
class Address
{
    /**
     * @var
     */
    private $id;
    /**
     * @var
     */
    private $name;
    /**
     * @var
     */
    private $company;
    /**
     * @var
     */
    private $person;
    /**
     * @var
     */
    private $city;
    /**
     * @var
     */
    private $postcode;
    /**
     * @var
     */
    private $street;
    /**
     * @var
     */
    private $houseNumber;
    /**
     * @var
     */
    private $flatNumber;
    /**
     * @var
     */
    private $email;
    /**
     * @var
     */
    private $phone;
    /**
     * @var
     */
    private $country;
    /**
     * @var
     */
    private $province;
    /**
     * @var
     */
    private $type;
    /**
     * @var
     */
    private $default;
    /**
     * @var
     */
    private $isCoverAddress;

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $company
     * @return $this
     */
    public function setCompany($company)
    {
        $this->company = $company;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param $person
     * @return $this
     */
    public function setPerson($person)
    {
        $this->person = $person;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * @param $city
     * @return $this
     */
    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param $postcode
     * @return $this
     */
    public function setPostcode($postcode)
    {
        $this->postcode = $postcode;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPostcode()
    {
        return $this->postcode;
    }

    /**
     * @param $street
     * @return $this
     */
    public function setStreet($street)
    {
        $this->street = $street;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @param $houseNumber
     * @return $this
     */
    public function setHouseNumber($houseNumber)
    {
        $this->houseNumber = $houseNumber;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHouseNumber()
    {
        return $this->houseNumber;
    }

    /**
     * @param $flatNumber
     * @return $this
     */
    public function setFlatNumber($flatNumber)
    {
        $this->flatNumber = $flatNumber;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFlatNumber()
    {
        return $this->flatNumber;
    }

    /**
     * @param $email
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param $phone
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
    public function getPhone()
    {
        return $this->phone;
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
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param $province
     * @return $this
     */
    public function setProvince($province)
    {
        $this->province = $province;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getProvince()
    {
        return $this->province;
    }

    /**
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $default
     * @return $this
     */
    public function setDefault($default)
    {
        $this->default = (bool)$default;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param $isCoverAddress
     * @return $this
     */
    public function setIsCoverAddress($isCoverAddress)
    {
        $this->isCoverAddress = (bool)$isCoverAddress;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsCoverAddress()
    {
        return $this->isCoverAddress;
    }

    /**
     * @return array
     */
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
