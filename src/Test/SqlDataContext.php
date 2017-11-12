<?php
namespace CFX\Persistence\Test;

class SqlDataContext extends \CFX\Persistence\Sql\AbstractDataContext {
    public function addDatasource($name, \CFX\Persistence\DatasourceInterface $datasource) {
        $this->datasources[$name] = $datasource;
        return $this;
    }

    protected function instantiateDatasource($name) {
        throw new \RuntimeException("Programmer: Something is trying to access a datasource named `$name` on this context. You need to set that datasource using the `addDatasource` method of this context.");
    }
}


