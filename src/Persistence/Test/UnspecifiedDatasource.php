<?php
namespace CFX\Persistence\Test;

class UnspecifiedDatasource extends \CFX\Persistence\AbstractDatasource {
    public function create(array $data=null) {
    }

    public function newCollection(array $data=null) {
    }

    public function get($q=null) {
    }

    protected function saveNew(\CFX\JsonApi\ResourceInterface $r) {
    }
    protected function saveExisting(\CFX\JsonApi\ResourceInterface $r) {
    }
    public function delete($r) {
    }
}

