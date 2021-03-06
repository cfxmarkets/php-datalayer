<?php
namespace CFX\Persistence\Test;

class RestPeopleDatasource extends \CFX\Persistence\Rest\AbstractDatasource {
    protected $resourceType = 'test-people';
    protected $requestWasDelegated = false;

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

