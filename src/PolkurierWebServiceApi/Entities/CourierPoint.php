<?php

namespace PolkurierWebServiceApi\Entities;

use JsonSerializable;
use PolkurierWebServiceApi\Util\Arr;

class CourierPoint implements JsonSerializable
{

    /**
     * @var string
     */
    private $id = '';

    /**
     * @var string
     */
    private $type = '';

    /**
     * @var string
     */
    private $provider = '';

    /**
     * @var string
     */
    private $city = '';

    /**
     * @var string
     */
    private $zip = '';

    /**
     * @var string
     */
    private $street = '';

    /**
     * @var string
     */
    private $description = '';

    /**
     * @var float
     */
    private $latitude = 0.0;

    /**
     * @var float
     */
    private $longitude = 0.0;

    /**
     * @var bool
     */
    private $cod = false;

    /**
     * @var bool
     */
    private $available = false;

    /**
     * @var string
     */
    private $status = '';

    /**
     * @var bool
     */
    private $send = false;

    /**
     * @var bool
     */
    private $collect = false;

    /**
     * @var string
     */
    private $openingHours = '';

    /**
     * @var bool
     */
    private $visible = false;

    /**
     * @var bool
     */
    private $requireApp = false;

    /**
     * @var string
     */
    private $requireAppMessage = '';

    /**
     * @var string[]
     */
    private $functions = [];

    /**
     * @param string $id
     * @return $this
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
     * @param string $type
     * @return $this
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
     * @param string $provider
     * @return $this
     */
    public function setProvider($provider)
    {
        $this->provider = (string)$provider;
        return $this;
    }

    /**
     * @return string
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @param string $city
     * @return $this
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
     * @param string $zip
     * @return $this
     */
    public function setZip($zip)
    {
        $this->zip = (string)$zip;
        return $this;
    }

    /**
     * @return string
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * @param string $street
     * @return $this
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
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = (string)$description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param float $latitude
     * @return $this
     */
    public function setLatitude($latitude)
    {
        $this->latitude = (float)$latitude;
        return $this;
    }

    /**
     * @return float
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param float $longitude
     * @return $this
     */
    public function setLongitude($longitude)
    {
        $this->longitude = (float)$longitude;
        return $this;
    }

    /**
     * @return float
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param bool $cod
     * @return $this
     */
    public function setCod($cod)
    {
        $this->cod = (bool)$cod;
        return $this;
    }

    /**
     * @return bool
     */
    public function getCod()
    {
        return $this->cod;
    }

    /**
     * @param bool $available
     * @return $this
     */
    public function setAvailable($available)
    {
        $this->available = (bool)$available;
        return $this;
    }

    /**
     * @return bool
     */
    public function getAvailable()
    {
        return $this->available;
    }

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = (string)$status;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param bool $send
     * @return $this
     */
    public function setSend($send)
    {
        $this->send = (bool)$send;
        return $this;
    }

    /**
     * @return bool
     */
    public function getSend()
    {
        return $this->send;
    }

    /**
     * @param bool $collect
     * @return $this
     */
    public function setCollect($collect)
    {
        $this->collect = (bool)$collect;
        return $this;
    }

    /**
     * @return bool
     */
    public function getCollect()
    {
        return $this->collect;
    }

    /**
     * @param string $openingHours
     * @return $this
     */
    public function setOpeningHours($openingHours)
    {
        $this->openingHours = (string)$openingHours;
        return $this;
    }

    /**
     * @return string
     */
    public function getOpeningHours()
    {
        return $this->openingHours;
    }

    /**
     * @param bool $visible
     * @return $this
     */
    public function setVisible($visible)
    {
        $this->visible = (bool)$visible;
        return $this;
    }

    /**
     * @return bool
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * @param bool $requireApp
     * @return $this
     */
    public function setRequireApp($requireApp)
    {
        $this->requireApp = (bool)$requireApp;
        return $this;
    }

    /**
     * @return bool
     */
    public function getRequireApp()
    {
        return $this->requireApp;
    }

    /**
     * @param string $requireAppMessage
     * @return $this
     */
    public function setRequireAppMessage($requireAppMessage)
    {
        $this->requireAppMessage = (string)$requireAppMessage;
        return $this;
    }

    /**
     * @return string
     */
    public function getRequireAppMessage()
    {
        return $this->requireAppMessage;
    }

    /**
     * @param string[] $functions
     * @return $this
     */
    public function setFunctions(array $functions)
    {
        $this->functions = $functions;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getFunctions()
    {
        return $this->functions;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
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

    /**
     * @param array $row
     * @return CourierPoint|null
     */
    public static function fromArray(array $row)
    {
        if (empty($row)) {
            return null;
        }
        return (new self())
            ->setId((string)Arr::get($row, 'id'))
            ->setType((string)Arr::get($row, 'type'))
            ->setProvider((string)Arr::get($row, 'provider'))
            ->setCity((string)Arr::get($row, 'city'))
            ->setZip((string)Arr::get($row, 'zip'))
            ->setStreet((string)Arr::get($row, 'street'))
            ->setDescription((string)Arr::get($row, 'description'))
            ->setLatitude((float)Arr::get($row, 'latitude'))
            ->setLongitude((float)Arr::get($row, 'longitude'))
            ->setCod((bool)Arr::get($row, 'cod'))
            ->setAvailable((bool)Arr::get($row, 'available'))
            ->setStatus((string)Arr::get($row, 'status'))
            ->setSend((bool)Arr::get($row, 'send'))
            ->setCollect((bool)Arr::get($row, 'collect'))
            ->setOpeningHours((string)Arr::get($row, 'openingHours'))
            ->setVisible((bool)Arr::get($row, 'visible'))
            ->setRequireApp((bool)Arr::get($row, 'requireApp'))
            ->setRequireAppMessage((string)Arr::get($row, 'requireAppMessage'))
            ->setFunctions((array)Arr::get($row, 'functions', []));
    }

}
