<?php
namespace CFX\Persistence;

abstract class AbstractDataContext implements DataContextInterface {
    /**
     * Cache of child datasources
     */
    protected $datasources;

    /**
     * Convenience method for turning datasource "getter" methods into read-only properties
     */
    public function __get($name) {
        if (!array_key_exists($name, $this->datasources)) $this->datasources[$name] = $this->instantiateDatasource($name);
        return $this->datasources[$name];
    }

    /**
     * Instantiate a client with the given `$name`
     */
    protected function instantiateDatasource($name) {
        throw new UnknownDatasourceException("Programmer: Don't know how to handle datasources of type `$name`. If you'd like to handle this, you should either add this datasource to the `instantiateDatasource` method in this class or create a derivative class to which to add it.");
    }

    /**
     * @see DataContextInterface::datasourceForType
     */
    public function datasourceForType($jsonApiType) {
        // Convert from dash-case to camelCase
        $type = explode('-', $jsonApiType);
        for($i = 0; $i < count($type); $i++) $type[$i] = ucfirst($type[$i]);
        $type = implode('', $type);

        // Try to return a datasource with that name
        return $this->$type;
    }

    /**
     * @see DataContextInterface::newResource
     */
    public function newResource($data=null, $type=null, $validAttrs=null, $validRels=null) {
        try {
            return $this->datasourceForType()->create($data);
        } catch (UnknownDatasourceException $e) {
            throw new UnknownResourceTypeException("Type `$type` is unknown. You can handle this type by adding a valid client for it to your DataContext.");
        }
    }

    /**
     * @see DataContextInterface::convertResource
     */
    public function convertResource(\CFX\JsonApi\ResourceInterface $src, $conversionType) {
        try {
            $datasource = $this->datasourceForType($src->getResourceType());
            return $datasource->convert($src, $conversionType);
        } catch (UnknownDatasourceException $e) {
            throw new UnknownResourceTypeException("Programmer: You've tried to convert a resource of type `{$src->getResourceType()}` to it's `$conversionType` format, but this data context (`".get_class($this)."`) doesn't know how to handle `{$src->getResourceType()}`-type resources.");
        }
    }
}

