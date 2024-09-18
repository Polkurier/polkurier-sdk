<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;
use JsonSerializable;

/**
 * Class AbstractMethod
 * @package PolkurierWebServiceApi\Methods
 *
 */
abstract class AbstractMethod implements MethodInterface
{
    /**
     * Dane zwrócone z API
     * @var array
     */
    protected $responseData = [];

    /**
     * Dane do wysłania w zapytaniu do API
     * @var array
     */
    protected $requestData = [];

    /**
     * Zwraca dane do wysłania w zapytaniu do API
     * @return array
     */
    public function getRequestData()
    {
        return $this->requestData;
    }

    /**
     * Ustawai dane do wysłanie w zapytaniu do API
     * @param array $data
     * @return $this
     */
    public function setRequestData(array $data)
    {
        $this->requestData = $data;
        return $this;
    }

    /**
     * Zwraca dane zwrócone w zapytaniu do API
     * @return array
     */
    public function getResponseData()
    {
        return $this->responseData;
    }

    /**
     * Ustawia dane zwrócone w zapytaniu do API
     * @param Response $response
     * @return $this
     */
    public function setResponseData(Response $response)
    {
        $this->responseData = $response->get('response');
        return $this;
    }

    /**
     * Zwraca obiekty zwrówone z API jako tablice
     * @return array
     */
    public function toArray()
    {
        $data = [];
        foreach ($this->responseData as $item) {
            if ($item instanceof JsonSerializable) {
                $data[] = $item->jsonSerialize();
            } else {
                $data[] = $item;
            }
        }
        return $data;
    }

    /**
     * Zwraca obiekt(y) zwrócone z API
     * @return mixed
     */
    public function getData()
    {
        return $this->getResponseData();
    }

}