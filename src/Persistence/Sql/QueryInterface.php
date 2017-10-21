<?php
namespace CFX\Sql;

interface QueryInterface {
    public function constructQuery();
    public function getDatabase();
    public function getParams();
}

