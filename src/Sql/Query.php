<?php
namespace CFX\Sql;

class Query implements QueryInterface {
    protected $properties = [ 'database', 'query', 'where', 'orderBy', 'limit', 'params' ];

    public function __construct(array $opts) {
        $properties = [];
        foreach($opts as $prop => $val) {
            if (!in_array($prop, $this->properties)) throw new \RuntimeException("Property not supported: `$prop`");
            $properties[$prop] = $val;
        }
        foreach($this->properties as $p) {
            if (!array_key_exists($p, $properties)) $properties[$p] = null;
        }
        $this->properties = $properties;

        if ($this->properties['database'] === null) $this->properties['database'] = 'default';
    }

    public function __get($prop) {
        if (!array_key_exists($prop, $this->properties)) throw new \RuntimeException("Unknown property `$prop`. Acceptable properties are `".implode('`, `', array_keys($this->properties))."`.");
        return $this->properties[$prop];
    }

    public function __set($prop, $value) {
        $setProp = "set".ucfirst($prop);
        if (!method_exists($this, $setProp)) throw new \RuntimeException("Can't set property `$prop`");
        $this->$setProp($value);
    }

    public function getDatabase() {
        return $this->properties['database'];
    }

    public function setDatabase($val) {
        $this->properties['database'] = $val;
        return $this;
    }

    public function getQuery() {
        return $this->query;
    }

    public function constructQuery() {
        $q = $this->query;
        if ($this->where) $q .= " WHERE $this->where";
        if ($this->orderBy) $q .= " ORDER BY $this->orderBy";
        if ($this->limit) $q .= " LIMIT $this->limit";
        return $q;
    } 

    public function setWhere($val) {
        $this->properties['where'] = $val;
        return $this;
    }

    public function setOrderBy($val) {
        $this->properties['orderBy'] = $val;
        return $this;
    }

    public function setLimit($val) {
        $this->properties['limit'] = $val;
        return $this;
    }

    public function getParams() {
        return $this->properties['params'];
    }

    public function setParams($val) {
        $this->properties['params'] = $val;
        return $this;
    }
}
