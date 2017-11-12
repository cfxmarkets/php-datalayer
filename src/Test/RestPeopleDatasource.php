<?php
namespace CFX\Persistence\Test;

class RestPeopleDatasource extends \CFX\Persistence\Rest\AbstractDatasource {
    protected $resourceType = 'test-people';
    protected $requestWasDelegated = false;

    public function create(array $data=null, $type = null) {
        return new Person($this, $data);
    }
    public function newCollection(array $data=[], $passthrough=false) {
        return new PeopleCollection($data);
    }

    public function testDSLParser($q=null) {
        return $this->parseDSL($q);
    }

    public function requestWasDelegated() {
        $delegated = $this->requestWasDelegated;
        $this->requestWasDelegated = false;
        return $delegated;
    }

    protected function sendRequest($method, $endpoint, array $params = []) {
        $this->requestWasDelegated = true;
        return parent::sendRequest($method, $endpoint, $params);
    }
}

