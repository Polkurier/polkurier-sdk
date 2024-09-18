<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;

/**
 * Class Heartbeat
 * @package PolkurierWebServiceApi\Methods
 *
 */
class Heartbeat extends AbstractMethod
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'heartbeat';
    }

    /**
     * @param Response $response
     * @return $this|AbstractMethod
     */
    public function setResponseData(Response $response)
    {
        $responseData = $response->get('response');
        $this->responseData = [
            'status' => $response->get('status'),
            'time' => $responseData['time'] ?? null
        ];
        return $this;
    }

    /**
     * Dane do wysłania w zapytaniu do API
     * Heartbeat nie wymaga dodatkowych danych.
     * @return array
     */
    public function getRequestData()
    {
        return [];
    }
}
