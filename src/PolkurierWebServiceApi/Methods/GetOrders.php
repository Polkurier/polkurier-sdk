<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

class GetOrders extends AbstractMethod
{
    private $orderno = '';
    private $packs = false;
    private $pagesize = 100;
    private $page = 1;
    private $status = '';
    private $items = false;
    private $files = false;

    public function getName()
    {
        return 'get_orders';
    }

    public function getRequestData()
    {
        $data = [];

        if ($this->orderno) {
            $data['orderno'] = $this->orderno;
        } else {
            $data['packs'] = $this->packs;
            $data['pagesize'] = $this->pagesize;
            $data['page'] = $this->page;
            if ($this->status) {
                $data['status'] = $this->status;
            }
            $data['items'] = $this->items;
            $data['files'] = $this->files;
        }

        return $data;
    }

    public function setOrderno($orderno)
    {
        $this->orderno = $orderno;
        return $this;
    }

    public function setPacks($packs)
    {
        $this->packs = (bool)$packs;
        return $this;
    }

    public function setPageSize($pagesize)
    {
        $this->pagesize = $pagesize;
        return $this;
    }

    public function setPage($page)
    {
        $this->page = $page;
        return $this;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function setItems($items)
    {
        $this->items = (bool)$items;
        return $this;
    }

    public function setFiles($files)
    {
        $this->files = (bool)$files;
        return $this;
    }

    public function setResponseData(Response $response)
    {
        $this->responseData = Arr::get($response->get('response'), 'result', []);
        return $this;
    }
}
