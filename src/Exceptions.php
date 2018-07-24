<?php
namespace CFX\Persistence;

/**
 * Base exception for persistence
 */
class Exception extends \RuntimeException { }

/** 
 * UnknownDatasourceException
 * Indicates that the requested datasource is not known to the system
 */
class UnknownDatasourceException extends \CFX\Persistence\Exception { }

/**
 * CorruptDataException
 * Indicates that the datasource contains bad or inconsistent data
 **/
class CorruptDataException extends \CFX\Persistence\Exception { }

/**
 * ResourceNotFoundException
 * Someone has sought a resource using an id that's not in the datasource
 */
class ResourceNotFoundException extends \CFX\Persistence\Exception { }

/**
 * UnknownResourceTypeException
 * The given context does not know how to deal with resources of the given type
 */
class UnknownResourceTypeException extends \CFX\Persistence\Exception { }

/**
 * InconsistentDataException
 * The given data object is inconsistent within the datasource
 */
class InconsistentDataException extends \CFX\Persistence\Exception { }

/**
 * DuplicateResource
 * A submitted resource conflicts with one that's already in the database
 */
class DuplicateResourceException extends \CFX\Persistence\Exception {
    protected $duplicate;
    public function setDuplicateResource(\CFX\JsonApi\ResourceInterface $resource) {
        $this->duplicate = $resource;
        return $this;
    }
    public function getDuplicateResource() { return $this->duplicate; }
}

/**
 * BadQueryException
 * Someone has passed an invalid DSL Query string to the parser
 */
class BadQueryException extends \CFX\Persistence\Exception {
    protected $badParams = [];
    protected $badQueryString;
    public function setBadParams(array $params) {
        $this->badParams = $params;
        return $this;
    }
    public function addBadParam($param) {
        $this->badParams[] = $param;
        return $this;
    }
    public function getBadParams() {
        return $this->badParams;
    }
    public function setBadQueryString($str) {
        $this->badQueryString = $str;
        return $this;
    }
    public function getBadQueryString() {
        return $this->badQueryString;
    }
}

