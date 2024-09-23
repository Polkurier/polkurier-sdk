<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\CourierPoint;
use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

class GetCourierPoint extends AbstractMethod
{
    /**
     * @var array
     */
    private $couriers = [];

    /**
     * @var string|null
     */
    private $searchquery = null;

    /**
     * @var array
     */
    private $functions = [];

    /**
     * @return string
     */
    public function getName()
    {
        return 'get_courier_point';
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        $data = [
            'couriers' => $this->couriers,
            'functions' => $this->functions
        ];

        if ($this->searchquery) {
            $data['searchquery'] = $this->searchquery;
        }

        return $data;
    }

    /**
     * Ustawia listę przewoźników
     * @param array $couriers
     * @return GetCourierPoint
     */
    public function setCouriers(array $couriers)
    {
        $this->couriers = $couriers;
        return $this;
    }

    /**
     * @param string|null $searchquery
     * @return GetCourierPoint
     */
    public function setSearchquery($searchquery)
    {
        $this->searchquery = $searchquery;
        return $this;
    }

    /**
     * @param array $functions
     * @return GetCourierPoint
     */
    public function setFunctions(array $functions)
    {
        $this->functions = $functions;
        return $this;
    }

    /**
     * @param Response $response
     * @return $this|AbstractMethod
     */
    public function setResponseData(Response $response)
    {
        $this->responseData = [];
        foreach ($response->get('response') as $row) {
            $point = new CourierPoint();
            $point->setId(Arr::get($row, 'id'))
                ->setType(Arr::get($row, 'type'))
                ->setProvider(Arr::get($row, 'provider'))
                ->setCity(Arr::get($row, 'city'))
                ->setZip(Arr::get($row, 'zip'))
                ->setStreet(Arr::get($row, 'street'))
                ->setDescription(Arr::get($row, 'description'))
                ->setLatitude(Arr::get($row, 'latitude'))
                ->setLongitude(Arr::get($row, 'longitude'))
                ->setCod(Arr::get($row, 'cod'))
                ->setAvailable(Arr::get($row, 'available'))
                ->setStatus(Arr::get($row, 'status'))
                ->setSend(Arr::get($row, 'send'))
                ->setCollect(Arr::get($row, 'collect'))
                ->setOpeningHours(Arr::get($row, 'openingHours'))
                ->setVisible(Arr::get($row, 'visible'))
                ->setRequireApp(Arr::get($row, 'requireApp'))
                ->setRequireAppMessage(Arr::get($row, 'requireAppMessage'))
                ->setFunctions(Arr::get($row, 'functions', []));

            $this->responseData[] = $point;
        }

        return $this;
    }
}
