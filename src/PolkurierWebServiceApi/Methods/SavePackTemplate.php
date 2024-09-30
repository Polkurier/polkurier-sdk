<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\PackTemplate;
use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

class SavePackTemplate extends AbstractMethod
{

    /**
     * @var array
     */
    private $templateData = [];

    /**
     * @return string
     */
    public function getName()
    {
        return 'save_pack_template';
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        return $this->templateData;
    }

    /**
     * @return SavePackTemplate
     */
    public function setTemplateData(PackTemplate $template)
    {
        $this->templateData = $template;
        return $this;
    }

    /**
     * @return SavePackTemplate
     */
    public function setResponseData(Response $response)
    {
        $data = $response->get('response');
        $this->responseData = new PackTemplate(
            (string)Arr::get($data, 'id', ''),
            (string)Arr::get($data, 'name', ''),
            (string)Arr::get($data, 'shipmenttype', ''),
            (int)Arr::get($data, 'length', 0),
            (int)Arr::get($data, 'width', 0),
            (int)Arr::get($data, 'height', 0),
            (float)Arr::get($data, 'weight', 0.0),
            (float)Arr::get($data, 'COD', 0.0),
            (float)Arr::get($data, 'insurance', 0.0),
            (string)Arr::get($data, 'description', ''),
            (string)Arr::get($data, 'type', '')
        );
        return $this;
    }

}
