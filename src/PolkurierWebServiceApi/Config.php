<?php

namespace PolkurierWebServiceApi;

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
    private $authLogin = '';

    /**
     * @var string
     */
    private $authToken = '';

    /**
     * @var string|int|null
     */
    private $platform = null;

    /**
     * @var string|null
     */
    private $platformVersion = null;

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

    /**
     * @param string|null $platformVersion
     * @return Config
     */
    public function setPlatformVersion($platformVersion)
    {
        $this->platformVersion = $platformVersion;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPlatformVersion()
    {
        return $this->platformVersion;
    }

    /**
     * @param int|string|null $platform
     * @return Config
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;
        return $this;
    }

    /**
     * @return int|string|null
     */
    public function getPlatform()
    {
        return $this->platform;
    }

}
