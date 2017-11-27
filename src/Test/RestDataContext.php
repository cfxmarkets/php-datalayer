<?php
namespace CFX\Persistence\Test;

class RestDataContext extends \CFX\Persistence\Rest\AbstractDataContext {
    protected static $apiName = 'tester';
    protected static $apiVersion = '1.0.0';

    public static function getStaticApiName() {
        return static::$apiName;
    }
    public static function getStaticApiVersion() {
        return static::$apiVersion;
    }
    public static function setApiName($str) {
        static::$apiName = $str;
    }
    public static function setApiVersion($str) {
        static::$apiVersion = $str;
    }

    public function getComposedUri($endpoint) {
        return $this->composeUri($endpoint);
    }

    public function instantiateDatasource($name)
    {
        if ($name === 'testPeople') {
            return new RestPeopleDatasource($this);
        }
    }
}

