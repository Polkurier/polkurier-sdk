<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Entities\PackTemplate;
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
     * @param PackTemplate $template
     * @return $this
     */
    public function setTemplateData(PackTemplate $template)
    {
        $this->templateData = $template->toArray();
        return $this;
    }

    /**
     * @param Response $response
     * @return self|AbstractMethod
     */
    public function setResponseData(Response $response)
    {
        $data = $response->get('response');
        $template = new PackTemplate();
        $template->setId(Arr::get($data, 'id'))
            ->setName(Arr::get($data, 'name'))
            ->setShipmentType(Arr::get($data, 'shipmenttype'))
            ->setLength(Arr::get($data, 'length'))
            ->setWidth(Arr::get($data, 'width'))
            ->setHeight(Arr::get($data, 'height'))
            ->setWeight(Arr::get($data, 'weight'))
            ->setCod(Arr::get($data, 'COD'))
            ->setInsurance(Arr::get($data, 'insurance'))
            ->setDescription(Arr::get($data, 'description'))
            ->setType(Arr::get($data, 'type'));

        $this->responseData = $template->toArray();

        return $this;
    }
}
