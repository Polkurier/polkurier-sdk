<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

class SavePackTemplate extends AbstractMethod
{
    private $templateData = [];

    public function getName()
    {
        return 'save_pack_template';
    }

    public function getRequestData()
    {
        return $this->templateData;
    }

    public function setTemplateData(array $templateData)
    {
        $this->templateData = $templateData;
        return $this;
    }

    public function setResponseData(Response $response)
    {
        $this->responseData = $response->get('response');
        return $this;
    }
}
