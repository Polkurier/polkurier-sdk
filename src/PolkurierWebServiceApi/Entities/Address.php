<?php

namespace PolkurierWebServiceApi\Entities;

class Address implements \JsonSerializable
{
    /**
     * @var string|null
     */
    private $id;
    /**
     * @var string|null
     */
    private $name;
    /**
     * @var string|null
     */
    private $company;
    /**
     * @var string|null
     */
    private $person;
    /**
     * @var string|null
     */
    private $city;
    /**
     * @var string|null
     */
    private $postcode;
    /**
     * @var string|null
     */
    private $street;
    /**
     * @var string|null
     */
    private $houseNumber;
    /**
     * @var string|null
     */
    private $flatNumber;
    /**
     * @var string|null
     */
    private $email;
    /**
     * @var string|null
     */
    private $phone;
    /**
     * @var string|null
     */
    private $country;
    /**
     * @var string|null
     */
    private $province;
    /**
     * @var string|null
     */
    private $type;
    /**
     * @var bool|null
     */
    private $default;
    /**
     * @var bool|null
     */
    private $isCoverAddress;

    /**
     * @param string|null $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string|null $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string|null $company
     * @return $this
     */
    public function setCompany($company)
    {
        $this->company = $company;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param string|null $person
     * @return $this
     */
    public function setPerson($person)
    {
        $this->person = $person;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * @param string|null $city
     * @return $this
     */
    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string|null $postcode
     * @return $this
     */
    public function setPostcode($postcode)
    {
        $this->postcode = $postcode;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPostcode()
    {
        return $this->postcode;
    }

    /**
     * @param string|null $street
     * @return $this
     */
    public function setStreet($street)
    {
        $this->street = $street;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @param string|null $houseNumber
     * @return $this
     */
    public function setHouseNumber($houseNumber)
    {
        $this->houseNumber = $houseNumber;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getHouseNumber()
    {
        return $this->houseNumber;
    }

    /**
     * @param string|null $flatNumber
     * @return $this
     */
    public function setFlatNumber($flatNumber)
    {
        $this->flatNumber = $flatNumber;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFlatNumber()
    {
        return $this->flatNumber;
    }

    /**
     * @param string|null $email
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string|null $phone
     * @return $this
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string|null $country
     * @return $this
     */
    public function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string|null $province
     * @return $this
     */
    public function setProvince($province)
    {
        $this->province = $province;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getProvince()
    {
        return $this->province;
    }

    /**
     * @param string|null $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param bool|null $default
     * @return $this
     */
    public function setDefault($default)
    {
        $this->default = $default;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param bool|null $isCoverAddress
     * @return $this
     */
    public function setIsCoverAddress($isCoverAddress)
    {
        $this->isCoverAddress = $isCoverAddress;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsCoverAddress()
    {
        return $this->isCoverAddress;
    }

    /**
     * @return array
     */
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
            'is_cover_address' => $this->isCoverAddress,
        ];
    }
}
