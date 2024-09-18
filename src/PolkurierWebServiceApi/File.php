<?php

namespace PolkurierWebServiceApi;

/**
 * Class File
 * @package PolkurierWebServiceApi
 *
 */
class File
{
    /**
     * @var mixed
     */
    private $content;

    public function __construct($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }
}