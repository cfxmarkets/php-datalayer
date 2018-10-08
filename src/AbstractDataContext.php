<?php
namespace CFX\Persistence;

abstract class AbstractDataContext implements DataContextInterface, \Psr\Log\LoggerAwareInterface {
    /**
     * @var DatasourceInterface[] Cache of child datasources
     */
    protected $datasources = [];

    /**
     * @var bool A debug flag to help in debugging
     */
    protected $debug;

    /**
     * @var \Psr\Log\LoggerInterface|null an optional logger with which to log messages
     */
    private $logger;




    /**
     * Convenience method for turning datasource "getter" methods into read-only properties
     *
     * @param string $name a camelCase datasource name
     * @return \CFX\JsonApi\DatasourceInterface
     * 
     * @throws \CFX\Persistence\UnknownDatasourceException
     */
    public function __get($name) {
        return $this->getNamedDatasource($name)
            ->setDebug($this->debug);
    }

    /**
     * @inheritdoc
     */
    public function datasourceForType($jsonApiType) {
        // Convert from dash-case to camelCase
        $type = explode('-', $jsonApiType);
        for($i = 1; $i < count($type); $i++) $type[$i] = ucfirst($type[$i]);
        $type = implode('', $type);

        // Try to return a datasource with that name
        return $this->getNamedDatasource($type)
            ->setDebug($this->debug);
    }

    /**
     * @inheritdoc
     */
    public function newResource($data=null, $type=null, $validAttrs=null, $validRels=null) {
        try {
            return $this->datasourceForType($type)->create($data, null, $validAttrs, $validRels);
        } catch (UnknownDatasourceException $e) {
            throw new UnknownResourceTypeException("Type `$type` is unknown. You can handle this type by adding a valid client for it to your DataContext (`".get_class($this)."`).");
        }
    }

    /**
     * @inheritdoc
     */
    public function convertResource(\CFX\JsonApi\ResourceInterface $src, $conversionType) {
        try {
            $datasource = $this->datasourceForType($src->getResourceType());
            return $datasource->convert($src, $conversionType);
        } catch (UnknownDatasourceException $e) {
            throw new UnknownResourceTypeException("Programmer: You've tried to convert a resource of type `{$src->getResourceType()}` to it's `$conversionType` format, but this data context (`".get_class($this)."`) doesn't know how to handle `{$src->getResourceType()}`-type resources.");
        }
    }




    /**
     * getNamedDatasource -- internal method for getting a datasource by name
     *
     * This method is meant to be the back-end (hence its protected visibility) for several different front-end
     * methods of getting a datasource, like the `__get` magic function and `datasourceForType`.
     *
     * @param string $name The camelCase name of a datasource to retrieve
     * @return \CFX\JsonApi\DatasourceInterface
     * 
     * @throws \CFX\Persistence\UnknownDatasourceException
     */
    protected function getNamedDatasource($name) {
        if (!array_key_exists($name, $this->datasources)) $this->datasources[$name] = $this->instantiateDatasource($name);
        return $this->datasources[$name];
    }

    /**
     * Instantiate a datasource with the given `$name`
     *
     * This is a factory method for instantiating datasources of various types. It should be overridden in child contexts to
     * provide arbitrary datasources
     *
     * @param string $name The camelCase name of the datasource you want to instantiate
     * @return \CFX\JsonApi\DatasourceInterface
     *
     * @throws \CFX\Persistence\UnknownDatasourceException
     */
    protected function instantiateDatasource($name) {
        throw new UnknownDatasourceException("Programmer: Don't know how to handle datasources of type `$name`. If you'd like to handle this, you should either add this datasource to the `instantiateDatasource` method in this class or create a derivative class to which to add it.");
    }



    // Logging

    /**
     * @inheritdoc from PSR-3
     */
    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Only provide one general log function that accepts a $level argument
     */
    protected function log($level, $message, array $context = [])
    {
        if ($this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }




    /**
     * @inheritdoc
     */
    public function setDebug($debug)
    {
        $this->debug = (bool)$debug;

        foreach($this->datasources as $d) {
            $d->setDebug($this->debug);
        }

        return $this;
    }
}

