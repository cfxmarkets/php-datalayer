<?php
namespace CFX\Persistence;

interface DSLQueryInterface {
    public static function parse($query);
    public function getWhere();
    public function getParams();
    public function requestingCollection();
}

