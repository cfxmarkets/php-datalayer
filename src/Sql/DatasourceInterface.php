<?php
namespace CFX\Sql;

interface DatasourceInterface extends \CFX\DatasourceInterface {
    /**
     * executeQuery -- Execute the given sql query
     *
     * @param QueryInterface $query A SQL Query to execute
     * @return array Raw data
     */
    public function executeQuery(QueryInterface $query);
}

