<?php
namespace CFX\Persistence\Sql;

interface QueryInterface {
    public function constructQuery();
    public function getDatabase();
    public function getParams();
}

