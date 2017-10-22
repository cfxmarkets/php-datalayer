<?php
namespace CFX\Persistence\Test;

class GenericDataContext extends \CFX\Persistence\AbstractDataContext {
    public function getDatasources() {
        return $this->datasources;
    }

    public function instantiateDatasource($name) {
        if ($name == 'testPeople') return new PeopleDatasource($this);
        if ($name == 'people') return new PeopleDatasource($this);
        if ($name == 'testTestPeople') return new PeopleDatasource($this);
        return parent::instantiateDatasource($name);
    }
}

