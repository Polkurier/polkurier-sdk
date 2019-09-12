<?php
namespace PolkurierWebServiceApi;

/**
 * Class Config
 * @package PolkurierWebServiceApi
 *
 */
class Config
{
    /**
     * @var string
     */
    private $apiUrl = 'https://api.polkurier.pl/';

    /**
     * @var int
     */
    private $apiTimeout = 30;

    /**
     * @var int
     */
    private $authLogin = '000000';

    /**
     * @var string
     */
    private $authToken = 'XYZ';

    /**
     * @return int
     */
    public function getApiTimeout()
    {
        return $this->apiTimeout;
    }

    /**
     * @param int $apiTimeout
     * @return Config
     */
    public function setApiTimeout($apiTimeout)
    {
        $this->apiTimeout = $apiTimeout;
        return $this;
    }

    /**
     * @return string
     */
    public function getAuthToken()
    {
        return $this->authToken;
    }

    /**
     * @param string $authToken
     * @return Config
     */
    public function setAuthToken($authToken)
    {
        $this->authToken = $authToken;
        return $this;
    }

    /**
     * @return int
     */
    public function getAuthLogin()
    {
        return $this->authLogin;
    }

    /**
     * @param int $authLogin
     * @return Config
     */
    public function setAuthLogin($authLogin)
    {
        $this->authLogin = $authLogin;
        return $this;
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    /**
     * @param string $apiUrl
     * @return Config
     */
    public function setApiUrl($apiUrl)
    {
        $this->apiUrl = $apiUrl;
        return $this;
    }
}