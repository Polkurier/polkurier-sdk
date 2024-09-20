<?php

namespace PolkurierWebServiceApi\Entities;

class CourierPoint implements \JsonSerializable
{
    /**
     * @var string|null
     */
    private $id;

    /**
     * @var string|null
     */
    private $type;

    /**
     * @var string|null
     */
    private $provider;

    /**
     * @var string|null
     */
    private $city;

    /**
     * @var string|null
     */
    private $zip;

    /**
     * @var string|null
     */
    private $street;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var float|null
     */
    private $latitude;

    /**
     * @var float|null
     */
    private $longitude;

    /**
     * @var bool|null
     */
    private $cod;

    /**
     * @var bool|null
     */
    private $available;

    /**
     * @var string|null
     */
    private $status;

    /**
     * @var bool|null
     */
    private $send;

    /**
     * @var bool|null
     */
    private $collect;

    /**
     * @var string|null
     */
    private $openingHours;

    /**
     * @var bool|null
     */
    private $visible;

    /**
     * @var bool|null
     */
    private $requireApp;

    /**
     * @var string|null
     */
    private $requireAppMessage;

    /**
     * @var array|null
     */
    private $functions = [];

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
     * @param string|null $provider
     * @return $this
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getProvider()
    {
        return $this->provider;
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
     * @param string|null $zip
     * @return $this
     */
    public function setZip($zip)
    {
        $this->zip = $zip;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getZip()
    {
        return $this->zip;
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
     * @param string|null $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param float|null $latitude
     * @return $this
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param float|null $longitude
     * @return $this
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param bool|null $cod
     * @return $this
     */
    public function setCod($cod)
    {
        $this->cod = $cod;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getCod()
    {
        return $this->cod;
    }

    /**
     * @param bool|null $available
     * @return $this
     */
    public function setAvailable($available)
    {
        $this->available = $available;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getAvailable()
    {
        return $this->available;
    }

    /**
     * @param string|null $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param bool|null $send
     * @return $this
     */
    public function setSend($send)
    {
        $this->send = $send;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getSend()
    {
        return $this->send;
    }

    /**
     * @param bool|null $collect
     * @return $this
     */
    public function setCollect($collect)
    {
        $this->collect = $collect;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getCollect()
    {
        return $this->collect;
    }

    /**
     * @param string|null $openingHours
     * @return $this
     */
    public function setOpeningHours($openingHours)
    {
        $this->openingHours = $openingHours;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getOpeningHours()
    {
        return $this->openingHours;
    }

    /**
     * @param bool|null $visible
     * @return $this
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * @param bool|null $requireApp
     * @return $this
     */
    public function setRequireApp($requireApp)
    {
        $this->requireApp = $requireApp;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getRequireApp()
    {
        return $this->requireApp;
    }

    /**
     * @param string|null $requireAppMessage
     * @return $this
     */
    public function setRequireAppMessage($requireAppMessage)
    {
        $this->requireAppMessage = $requireAppMessage;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRequireAppMessage()
    {
        return $this->requireAppMessage;
    }

    /**
     * @param array|null $functions
     * @return $this
     */
    public function setFunctions($functions)
    {
        $this->functions = $functions;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getFunctions()
    {
        return $this->functions;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'provider' => $this->provider,
            'city' => $this->city,
            'zip' => $this->zip,
            'street' => $this->street,
            'description' => $this->description,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'cod' => $this->cod,
            'available' => $this->available,
            'status' => $this->status,
            'send' => $this->send,
            'collect' => $this->collect,
            'openingHours' => $this->openingHours,
            'visible' => $this->visible,
            'requireApp' => $this->requireApp,
            'requireAppMessage' => $this->requireAppMessage,
            'functions' => $this->functions
        ];
    }
}
