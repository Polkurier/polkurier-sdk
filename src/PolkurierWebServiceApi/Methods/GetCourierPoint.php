<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\CourierPoint;
use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

class GetCourierPoint extends AbstractMethod
{

    /**
     * @var string[]
     */
    private $couriers = [];

    /**
     * @var string
     */
    private $searchQuery = '';

    /**
     * @var string[]
     */
    private $functions = [];

    /**
     * @var string
     */
    private $id = '';

    /**
     * @var int
     */
    private $limit = 0;

    /**
     * @var int
     */
    private $page = 0;

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

        if ($this->searchQuery !== '') {
            $data['searchquery'] = $this->searchQuery;
        }

        if ($this->id !== '') {
            $data['id'] = $this->id;
        }

        if ($this->limit > 0) {
            $data['limit'] = $this->limit;
        }

        if ($this->page > 1) {
            $data['page'] = $this->page;
        }

        return $data;
    }

    /**
     * Ustawia listę przewoźników
     * @param string[] $couriers
     * @return GetCourierPoint
     */
    public function setCouriers(array $couriers)
    {
        Arr::assertStrings($couriers);
        $this->couriers = $couriers;
        return $this;
    }

    /**
     * @param string $searchQuery
     * @return GetCourierPoint
     */
    public function setSearchQuery($searchQuery)
    {
        $this->searchQuery = (string)$searchQuery;
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
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param int $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * @param int $page
     */
    public function setPage($page)
    {
        $this->page = $page;
    }

    /**
     * @return GetCourierPoint
     */
    public function setResponseData(Response $response)
    {
        $this->responseData = [];
        if ($this->id) {
            $row = (array)$response->get('response', []);
            $this->responseData = !empty($row)
                ? CourierPoint::fromArray((array)$response->get('response'))
                : null;
        } else {
            foreach ($response->get('response', []) as $row) {
                $this->responseData[] = CourierPoint::fromArray((array)$row);
            }
        }
        return $this;
    }

}
