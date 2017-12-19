<?php
namespace CFX\Persistence;

interface DataContextInterface {
    /**
     * datasourceForType -- Use the supplied `type` parameter to try to get a datasource in this context
     *
     * @param string $jsonApiType The json-api-formatted type parameter (e.g., `site-users`)
     * @return DatasourceInterface The desired datasource (e.g., `siteUsers`)
     */
    public function datasourceForType($jsonApiType);

    /**
     * newResource -- Mint a new resource
     *
     * Attempts to delegate this functionality to child datasources. If no child is available to create the given
     * resource, an exception is thrown.
     *
     * @param array|null $data Optional array of user data with which to initialize the resource
     * @param string|null $type Optional type of resource
     * @param array|null $validAttrs Optional array of valid attributes (for generic resources)
     * @param array|null $validRels Optional array of valid relationships (for generic resources)
     *
     * @return \CFX\JsonApi\ResourceInterface
     *
     * @throws UnknownResourceTypeException on attempt to intantiate unknown resource type
     */
    public function newResource($data=null, $type=null, $validAttrs=null, $validRels=null);

    /**
     * convertResource -- Convert a resource to a related class
     *
     * This also delegates to child datasources
     *
     * @see DatasourceInterface::convert
     */
    public function convertResource(\CFX\JsonApi\ResourceInterface $src, $conversionType);


    /**
     * setDebug -- set the debug flag
     *
     * This flag is to be passed on to child datasources via their own `setDebug` methods
     *
     * @param bool $debug Sets the debug flag to the given value
     * @return static
     */
    public function setDebug($debug);
}

