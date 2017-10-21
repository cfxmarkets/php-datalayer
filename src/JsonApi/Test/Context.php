<?php
namespace CFX\JsonApi\Test;

class Datasource extends \CFX\JsonApi\Datasource {
    /**
     * Stub this out so that we return a GenericResource for any requested type
     */
    public function newJsonApiResource($data=null, $type=null) {
        if ($type == 'test-users') return new User($this, $data);
        return new \CFX\JsonApi\GenericResource($this, $data);
    }
}

