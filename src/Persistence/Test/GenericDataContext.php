<?php
namespace CFX\Persistence\Test;

class GenericDataContext extends \CFX\Persistence\AbstractDataContext {
    public function getDatasources() {
        return $this->datasources;
    }
}

