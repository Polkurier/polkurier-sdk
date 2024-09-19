<?php

namespace PolkurierWebServiceApi\Entities;

class CourierPoint
{
    private $id;
    private $type;
    private $provider;
    private $city;
    private $zip;
    private $street;
    private $description;
    private $latitude;
    private $longitude;
    private $cod;
    private $available;
    private $status;
    private $send;
    private $collect;
    private $openingHours;
    private $visible;
    private $requireApp;
    private $requireAppMessage;
    private $functions = [];

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
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

    public function setProvider($provider)
    {
        $this->provider = $provider;
        return $this;
    }

    public function getProvider()
    {
        return $this->provider;
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

    public function setZip($zip)
    {
        $this->zip = $zip;
        return $this;
    }

    public function getZip()
    {
        return $this->zip;
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

    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLatitude()
    {
        return $this->latitude;
    }

    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getLongitude()
    {
        return $this->longitude;
    }

    public function setCod($cod)
    {
        $this->cod = $cod;
        return $this;
    }

    public function getCod()
    {
        return $this->cod;
    }

    public function setAvailable($available)
    {
        $this->available = $available;
        return $this;
    }

    public function getAvailable()
    {
        return $this->available;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setSend($send)
    {
        $this->send = $send;
        return $this;
    }

    public function getSend()
    {
        return $this->send;
    }

    public function setCollect($collect)
    {
        $this->collect = $collect;
        return $this;
    }

    public function getCollect()
    {
        return $this->collect;
    }

    public function setOpeningHours($openingHours)
    {
        $this->openingHours = $openingHours;
        return $this;
    }

    public function getOpeningHours()
    {
        return $this->openingHours;
    }

    public function setVisible($visible)
    {
        $this->visible = $visible;
        return $this;
    }

    public function getVisible()
    {
        return $this->visible;
    }

    public function setRequireApp($requireApp)
    {
        $this->requireApp = $requireApp;
        return $this;
    }

    public function getRequireApp()
    {
        return $this->requireApp;
    }

    public function setRequireAppMessage($requireAppMessage)
    {
        $this->requireAppMessage = $requireAppMessage;
        return $this;
    }

    public function getRequireAppMessage()
    {
        return $this->requireAppMessage;
    }

    public function setFunctions(array $functions)
    {
        $this->functions = $functions;
        return $this;
    }

    public function getFunctions()
    {
        return $this->functions;
    }

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
