<?php
namespace CFX\Persistence\Test;

class PeopleDatasource extends \CFX\Persistence\AbstractDatasource implements \CFX\JsonApi\DatasourceInterface {
    protected $resourceType = 'test-people';
    protected $saveType;

    public function create(array $data=null) {
        return new Person($this, $data);
    }
    public function newCollection(array $data=[], $passthrough=false) {
        return new PeopleCollection($data);
    }
    public function get($q=null) {
        $data = [
            [
                "id" => 1,
                "type" => "test-people",
                "attributes" => [
                    "name" => "Jim Chavo",
                ],
            ],
        ];

        $q = $this->parseDSL($q);
        return $this->inflateData($data, $q->requestingCollection());
    }
    protected function saveNew(\CFX\JsonApi\ResourceInterface $r) {
        $this->saveType = 'new';
    }
    protected function saveExisting(\CFX\JsonApi\ResourceInterface $r) {
        $this->saveType = 'existing';
    }
    public function delete($r) {
    }

    // Test methods
    public function setCurrentData($data) {
        $this->currentData = $data;
        return $this;
    }
    public function getSaveType() {
        $type = $this->saveType;
        $this->saveType = null;
        return $type;
    }

    public function testDSLParser($q=null) {
        return $this->parseDSL($q);
    }
}

