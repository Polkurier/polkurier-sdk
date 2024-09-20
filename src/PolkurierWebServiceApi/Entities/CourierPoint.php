<?php

namespace PolkurierWebServiceApi\Entities;

class CourierPoint
{
    /**
     * @var
     */
    private $id;
    /**
     * @var
     */
    private $type;
    /**
     * @var
     */
    private $provider;
    /**
     * @var
     */
    private $city;
    /**
     * @var
     */
    private $zip;
    /**
     * @var
     */
    private $street;
    /**
     * @var
     */
    private $description;
    /**
     * @var
     */
    private $latitude;
    /**
     * @var
     */
    private $longitude;
    /**
     * @var
     */
    private $cod;
    /**
     * @var
     */
    private $available;
    /**
     * @var
     */
    private $status;
    /**
     * @var
     */
    private $send;
    /**
     * @var
     */
    private $collect;
    /**
     * @var
     */
    private $openingHours;
    /**
     * @var
     */
    private $visible;
    /**
     * @var
     */
    private $requireApp;
    /**
     * @var
     */
    private $requireAppMessage;
    /**
     * @var array
     */
    private $functions = [];

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
     * @param $provider
     * @return $this
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getProvider()
    {
        return $this->provider;
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
     * @param $zip
     * @return $this
     */
    public function setZip($zip)
    {
        $this->zip = $zip;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getZip()
    {
        return $this->zip;
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
     * @param $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param $latitude
     * @return $this
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param $longitude
     * @return $this
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param $cod
     * @return $this
     */
    public function setCod($cod)
    {
        $this->cod = $cod;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCod()
    {
        return $this->cod;
    }

    /**
     * @param $available
     * @return $this
     */
    public function setAvailable($available)
    {
        $this->available = $available;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAvailable()
    {
        return $this->available;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $send
     * @return $this
     */
    public function setSend($send)
    {
        $this->send = $send;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSend()
    {
        return $this->send;
    }

    /**
     * @param $collect
     * @return $this
     */
    public function setCollect($collect)
    {
        $this->collect = $collect;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCollect()
    {
        return $this->collect;
    }

    /**
     * @param $openingHours
     * @return $this
     */
    public function setOpeningHours($openingHours)
    {
        $this->openingHours = $openingHours;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOpeningHours()
    {
        return $this->openingHours;
    }

    /**
     * @param $visible
     * @return $this
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * @param $requireApp
     * @return $this
     */
    public function setRequireApp($requireApp)
    {
        $this->requireApp = $requireApp;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRequireApp()
    {
        return $this->requireApp;
    }

    /**
     * @param $requireAppMessage
     * @return $this
     */
    public function setRequireAppMessage($requireAppMessage)
    {
        $this->requireAppMessage = $requireAppMessage;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRequireAppMessage()
    {
        return $this->requireAppMessage;
    }

    /**
     * @param array $functions
     * @return $this
     */
    public function setFunctions(array $functions)
    {
        $this->functions = $functions;
        return $this;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return $this->functions;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'type' => $this->getType(),
            'provider' => $this->getProvider(),
            'city' => $this->getCity(),
            'zip' => $this->getZip(),
            'street' => $this->getStreet(),
            'description' => $this->getDescription(),
            'latitude' => $this->getLatitude(),
            'longitude' => $this->getLongitude(),
            'cod' => $this->getCod(),
            'available' => $this->getAvailable(),
            'status' => $this->getStatus(),
            'send' => $this->getSend(),
            'collect' => $this->getCollect(),
            'openingHours' => $this->getOpeningHours(),
            'visible' => $this->getVisible(),
            'requireApp' => $this->getRequireApp(),
            'requireAppMessage' => $this->getRequireAppMessage(),
            'functions' => $this->getFunctions()
        ];
    }
}
