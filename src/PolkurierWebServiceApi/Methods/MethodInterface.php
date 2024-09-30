<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;

interface MethodInterface
{

    public function getName();

    public function setRequestData(array $data);

    public function getRequestData();

    public function setResponseData(Response $response);

    public function getResponseData();

    public function toArray();

    public function getData();

}
