<?php

namespace PolkurierWebServiceApi\Entities;

class CheckContentCourierService implements CourierServiceInterface
{

    /**
     * @var bool
     */
    private $checkContent;

    /**
     * CheckContentCourierService constructor.
     * @param bool $checkContent
     */
    public function __construct($checkContent = false)
    {
        $this->checkContent = (bool)$checkContent;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'CHECK_CONTENT' => $this->checkContent
        ];
    }

    /**
     * @return bool
     */
    public function isCheckContent()
    {
        return $this->checkContent;
    }

    /**
     * @param bool $checkContent
     */
    public function setCheckContent($checkContent)
    {
        $this->checkContent = (bool)$checkContent;
    }

}
