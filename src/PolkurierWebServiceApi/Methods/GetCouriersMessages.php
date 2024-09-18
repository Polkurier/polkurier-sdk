<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\CourierMessage;
use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Type\CourierMessageSeverity;
use PolkurierWebServiceApi\Type\CourierMessageType;
use PolkurierWebServiceApi\Util\Arr;

/**
 * Class GetStatus
 * @package PolkurierWebServiceApi\Methods
 *
 */
class GetCouriersMessages extends AbstractMethod
{
    /**
     * @var string
     */
    private $courier = '';

    /**
     * @return string
     */
    public function getName()
    {
        return 'get_couriers_messages';
    }

    /**
     * @param string $courier
     * @return $this
     */
    public function setCourier($courier)
    {
        $this->courier = $courier;
        return $this;
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        return [
            'courier' => $this->courier
        ];
    }

    /**
     * @param Response $response
     * @return $this|AbstractMethod
     */
    public function setResponseData(Response $response)
    {
        $this->responseData = [];
        foreach ((array)$response->get('response', []) as $message) {
            $this->responseData[] = new CourierMessage(
                (string)Arr::get($message, 'courier'),
                (string)Arr::get($message, 'message', ''),
                (string)Arr::get($message, 'type', CourierMessageType::MESSAGE),
                (string)Arr::get($message, 'severity', CourierMessageSeverity::INFO),
                (int)Arr::get($message, 'hide_timeout', 0)
            );
        }
        return $this;
    }
}
