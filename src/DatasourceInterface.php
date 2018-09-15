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

    /**
     * Validate that the incoming data conforms to the standards of this particular datasource. For example, you would
     * use this to ensure that duplicate resources are not inserted, or that certain related resources actually exist
     * in the database (if desired).
     *
     * Input should be \CFX\JsonApi\Resource object with changed fields accessible via `getChanges`
     *
     * @param \CFX\JsonApi\ResourceInterface $r The resource being validated
     * @return \CFX\JsonApi\DatasourceInterface self
     */
    public function validateIncoming(\CFX\JsonApi\ResourceInterface $r);
}

