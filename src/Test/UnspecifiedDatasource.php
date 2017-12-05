<?php
namespace CFX\Persistence\Test;

class UnspecifiedDatasource extends \CFX\Persistence\AbstractDatasource {
    public function getClassMap()
    {
        return [];
    }

    public function create(array $data=null, $type = null) {
    }

    public function newCollection(array $data=null) {
    }

    public function get($q=null) {
    }

    public function getDuplicate(\CFX\JsonApi\ResourceInterface $r)
    {
        throw new \CFX\Persistence\ResourceNotFoundException();
    }

    protected function saveNew(\CFX\JsonApi\ResourceInterface $r) {
    }
    protected function saveExisting(\CFX\JsonApi\ResourceInterface $r) {
    }
    public function delete($r) {
    }
}

