<?php
namespace PolkurierWebServiceApi;

use PolkurierWebServiceApi\Exception\ErrorException;
use PolkurierWebServiceApi\Util\Arr;

/**
 * Class Response
 * @package PolkurierWebService
 *
 */
class Response
{
    /**
     * @var string
     */
    private $responseString;

    /**
     * @var mixed
     */
    private $responseData;

    /**
     * Response constructor.
     * @param $responseString
     * @throws \PolkurierWebServiceApi\Exception\ErrorException
     */
    public function __construct($responseString)
    {
        $this->responseString = $responseString;
        $this->responseData = json_decode($responseString, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ErrorException('Błąd parsowania JSON: ' . json_last_error_msg());
        }
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        if (is_array($this->responseData)) {
            return Arr::get($this->responseData, $key, $default);
        }
        return $default;
    }

    /**
     * @return string
     */
    public function getResponseString()
    {
        return $this->responseString;
    }

}