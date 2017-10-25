<?php
namespace CFX\Persistence;

interface DSLQueryInterface {
    public static function parse($query);
    public function getId();
    public function getWhere();
    public function getParams();
    public function requestingCollection();
}

