<?php
namespace CFX\Persistence;

interface DatasourceInterface extends \CFX\JsonApi\DatasourceInterface
{
    /**
     * setDebug -- set the debug flag
     *
     * @param bool $debug Sets the debug flag to the given value
     * @return static Returns the object itself.
     */
    public function setDebug($debug);

    /**
     * getCurrentData -- get a JSON API-format array representing data that is prepared for a resource object to extract
     * on initialization.
     *
     * This method is meant to be used by resource objects on construct or on `restoreFromData` to get data prepared
     * for them.
     *
     * @return array A JSON API-format array representing resource data
     */
    public function getCurrentData();
}

