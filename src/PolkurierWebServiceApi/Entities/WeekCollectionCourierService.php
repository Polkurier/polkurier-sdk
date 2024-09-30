<?php

namespace PolkurierWebServiceApi\Entities;

class WeekCollectionCourierService implements CourierServiceInterface
{

    /**
     * @var bool
     */
    private $week_collection;

    /**
     * @param bool $week_collection
     */
    public function __construct($week_collection = false)
    {
        $this->week_collection = (bool)$week_collection;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'WEEK_COLLECTION' => $this->week_collection
        ];
    }

    /**
     * @return bool
     */
    public function isWeekCollection()
    {
        return $this->week_collection;
    }

    /**
     * @param bool $week_collection
     */
    public function setWeekCollection($week_collection)
    {
        $this->week_collection = (bool)$week_collection;
    }

}
