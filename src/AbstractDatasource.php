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
    protected function inflateData(array $obj, $isCollection) {
        foreach($obj as $k => $o) {
            $this->currentData = $o;
            $obj[$k] = $this->create();
            if ($this->currentData !== null) throw new \RuntimeException("There appears to be leftover data in the cache. You should make sure that all data objects call this database's `getCurrentData` method from within their constructors. (Offending class: `".get_class($obj[$k])."`. Did you overwrite the default constructor?)");
        }
        return $isCollection ?
            $this->newCollection($obj) :
            $obj[0]
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


    /**
     * Convert data to JsonApi format
     *
     * @param string $type What to put for the `type` parameter
     * @param array $records The rows of data to convert
     * @param array $rels An optional list of relationships. Array should be indexed by FIELD NAME, and each item
     * should be an array whose first value is the `type` of object that the relationship deals with and whose
     * second value is the `name` of the relationship.
     * @param string $idField The name of the field that contains the object's ID (Default: 'id')
     * @return array A collection of resources represented in json api format
     */

    protected function convertToJsonApi($type, $records, $rels=[], $idField='id') {
        if (count($records) == 0) return $records;

        $jsonapi = [];
        foreach($records as $n => $r) {
            $jsonapi[$n] = [
                'type' => $type,
                'id' => $r[$idField],
                'attributes' => [],
            ];
            if (count($rels) > 0) $jsonapi[$n]['relationships'] = [];

            foreach ($r as $field => $v) {
                if ($field == $idField) continue;
                if (array_key_exists($field, $rels)) {
                    // For to-many relationships
                    if (is_array($v)) {
                        $rel = [];
                        foreach($v as $relId) {
                            $rel[] = [
                                "data" => [
                                    "type" => $rels[$field][0],
                                    "id" => $relId,
                                ],
                            ];
                        }

                    // For to-one relationships
                    } else {
                        if (!$v) $rel = [ "data" => null ];
                        else $rel = [
                            "data" => [
                                "type" => $rels[$field][0],
                                "id" => $v,
                            ]
                        ];
                    }

                    // Add relationship
                    $jsonapi[$n]['relationships'][$rels[$field][1]] = $rel;
                } else {
                    $jsonapi[$n]['attributes'][$field] = $v;
                }
            }
        }

        return $jsonapi;
    }
}

