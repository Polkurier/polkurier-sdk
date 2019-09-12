<?php
namespace PolkurierWebServiceApi;

use PolkurierWebServiceApi\Exception\FatalException;

/**
 * Class HTTPClient
 * @package PolkurierWebServiceApi
 *
 */
class HTTPClient
{
    /**
     * @var bool
     */
    private $returnTransfer = true;
    /**
     * @var bool
     */
    private $sslVerifyPeer = true;
    /**
     * @var int
     */
    private $sslVerifyHost = 2;

    /**
     * HTTPClient constructor.
     * @param \PolkurierWebServiceApi\Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param \PolkurierWebServiceApi\Request $request
     * @return array
     */
    protected function prepareHeaders(Request $request)
    {
        $headers = [];
        foreach ($request->getHeaders() as $key => $val) {
            $headers[] = $key .':'. $val;
        }
        return $headers;
    }

    /**
     * @param \PolkurierWebServiceApi\Request $request
     * @return string
     */
    protected function preparePayload(Request $request)
    {
        return json_encode($request->getBody());
    }

    /**
     * @param \PolkurierWebServiceApi\Request $request
     * @return \PolkurierWebServiceApi\Response
     * @throws \PolkurierWebServiceApi\Exception\ErrorException
     * @throws \PolkurierWebServiceApi\Exception\FatalException
     */
    public function request(Request $request)
    {
        $headers = $this->prepareHeaders($request);
        $payload = $this->preparePayload($request);
        $client = curl_init();
        curl_setopt($client, CURLOPT_URL, $this->config->getApiUrl());
        curl_setopt($client, CURLOPT_POST, true);
        curl_setopt($client, CURLOPT_RETURNTRANSFER, $this->returnTransfer);
        curl_setopt($client, CURLOPT_SSL_VERIFYPEER, $this->sslVerifyPeer);
        curl_setopt($client, CURLOPT_SSL_VERIFYHOST, $this->sslVerifyHost);
        curl_setopt($client, CURLOPT_TIMEOUT, $this->config->getApiTimeout());
        curl_setopt($client, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($client, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($client);
        $http_code = curl_getinfo($client,CURLINFO_HTTP_CODE);
        curl_close($client);
        var_dump($result);
        if($http_code == 200) {
            if (!$result){
                throw new FatalException('Nie można połączyć się z interfejsem API');
            }
            return new Response($result);
        }else {
            throw new FatalException('Nie można połączyć się z interfejsem API. HTTP_CODE. '.$http_code);

        }
    }

}
