<?php
namespace CFX\Persistence;

abstract class AbstractDatasource implements DatasourceInterface {
    protected $resourceType;
    protected $context;
    protected $currentData;

    public function __construct(DataContextInterface $context) {
        if ($this->getResourceType() === null) throw new \RuntimeException("Programmer: You need to define this subclient's `\$resourceType` attribute. This should match the type of resources that this client deals in.");
        $this->context = $context;
    }

    public function convert(\CFX\JsonApi\ResourceInterface $src, $convertTo) {
        throw new \RuntimeException("Programmer: Don't know how to convert resources to type `$convertTo`.");
    }

    public function newCollection(array $data=null) {
        return new \CFX\JsonApi\ResourceCollection($data);
    }

    public function getResourceType() {
        return $this->resourceType;
    }

    /**
     * @see DatasourceInterface::getCurrentData
     */
    public function getCurrentData() {
        $data = $this->currentData;
        $this->currentData = null;
        return $data;
    }

    public function save(\CFX\JsonApi\ResourceInterface $r) {
        // If we're trying to save with errors, throw exception
        if ($r->hasErrors()) {
            $e = new \CFX\JsonApi\BadInputException("Bad input");
            $e->setInputErrors($r->getErrors());
            throw $e;
        }

        // If it exists already, update it
        if ($r->getId()) $this->saveExisting($r);

        // Else, create it
        else $this->saveNew($r);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function inflateRelated(array $data) {
        return $this->context->newResource($data, array_key_exists('type', $data) ? $data['type'] : null);
    }

    abstract protected function saveNew(\CFX\JsonApi\ResourceInterface $r);
    abstract protected function saveExisting(\CFX\JsonApi\ResourceInterface $r);

    /**
     * inflateData -- use the provided data to create a Resource or ResourceCollection object
     *
     * @param array $data Should always be in the format of a "table with rows", i.e., `[ ["id" => "1234",
     * "type" => "examples", "attributes" => [] ] ]`
     * @param bool $isCollection Whether or not this data represents a collection
     * @return \CFX\JsonApi\ResourceInterface|\CFX\JsonApi\ResourceCollectionInterface
     */
    protected function inflateData(array $data, $isCollection) {
        if (!$isCollection && count($data) == 0) throw new ResourceNotFoundException("Sorry, we couldn't find some of the data you were looking for.");

        foreach($data as $k => $o) {
            $this->currentData = $o;
            $data[$k] = $this->create(null, 'private');
            if ($this->currentData !== null) throw new \RuntimeException("There appears to be leftover data in the cache. You should make sure that all data objects call this database's `getCurrentData` method from within their constructors. (Offending class: `".get_class($data[$k])."`. Did you overwrite the default constructor?)");
        }
        return $isCollection ?
            $this->newCollection($data) :
            $data[0]
        ;
    }

    /**
     * parseDSL -- parse the DSL query that the user sent in
     *
     * @param string $query A query written in a domain-specific query language
     * @return DSLQueryInterface
     */
    protected function parseDSL($query) {
        return GenericDSLQuery::parse($query);
    }
}

