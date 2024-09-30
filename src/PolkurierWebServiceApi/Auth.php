<?php

namespace PolkurierWebServiceApi;

class Auth
{

    /**
     * @var string
     */
    private $login;

    /**
     * @var string
     */
    private $token;

    public function __construct(Config $config = null)
    {
        if ($config) {
            $this->login = $config->getAuthLogin();
            $this->token = $config->getAuthToken();
        }
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @param string $login
     * @return Auth
     */
    public function setLogin($login)
    {
        $this->login = $login;
        return $this;
    }

    /**
     * @param string $token
     * @return Auth
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

}
