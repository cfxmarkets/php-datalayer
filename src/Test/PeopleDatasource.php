<?php
namespace CFX\Persistence\Test;

class PeopleDatasource extends \CFX\Persistence\AbstractDatasource implements \CFX\JsonApi\DatasourceInterface {
    protected $resourceType = 'test-people';
    protected $saveType;

    public function getClassMap()
    {
        return [
            'public' => "\\CFX\\Persistence\\Test\Person",
            'private' => "\\CFX\\Persistence\\Test\Person",
        ];
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
    public function getDuplicate(\CFX\JsonApi\ResourceInterface $r)
    {
        throw new \CFX\Persistence\ResourceNotFoundException();
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

    public function testInflateData(array $obj, $isCollection) {
        return $this->inflateData($obj, $isCollection);
    }
}

