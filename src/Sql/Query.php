<?php
namespace CFX\Persistence\Sql;

/**
 * A very basic SQL Query abstraction
 *
 * This class is not meant to be a fully-featured Query builder class. It is only meant to provide a uniform interface
 * that can be used to encapsulate the required values for use with a complete PDO query. That is, it is intented to be
 * useful by any database class whose underlying mechansim is a PDO to first prepare, then execute a valid SQL Query.
 */
class Query implements QueryInterface {
    /**
     * @var string[] An array of properties that may be accessed (readonly) on objects of this class
     */
    protected $properties = [ 'database', 'query', 'where', 'orderBy', 'limit', 'params' ];

    /**
     * Construct a new SQL Query object
     *
     * @param array $opts The properties of this query (must contain only the properties listed in `-$this->properties`
     * @return static
     */
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

    /**
     * Magic method for getting allowed properties
     *
     * @param string $prop The name of the property to get
     * @return mixed The property value
     */
    public function __get($prop) {
        if (!array_key_exists($prop, $this->properties)) throw new \RuntimeException("Unknown property `$prop`. Acceptable properties are `".implode('`, `', array_keys($this->properties))."`.");
        return $this->properties[$prop];
    }

    /**
     * Magic method for setting allowed properties
     *
     * @param string $prop The name of the property to set
     * @param mixed $value The value of the property
     * @return void
     */
    public function __set($prop, $value) {
        $setProp = "set".ucfirst($prop);
        if (!method_exists($this, $setProp)) throw new \RuntimeException("Can't set property `$prop`");
        $this->$setProp($value);
    }

    /**
     * @inheritdoc
     */
    public function getDatabase() {
        return $this->properties['database'];
    }

    /**
     * Set the name of the database for this query
     *
     * This should be one of the keys pointing to a PDO (or PDO enclosure) in the Context
     *
     * @param string $val
     * @return static
     */
    public function setDatabase($val) {
        $this->properties['database'] = $val;
        return $this;
    }

    /**
     * Set the base query.
     *
     * Because this is a very free-form class, the "query" parameter should contain everything up to the "WHERE" clause. For example,
     * a valid value for this might be, `SELECT val1, val2, val3 FROM somedb.sometable`
     *
     * @return string
     */
    public function getQuery() {
        return $this->query;
    }

    /**
     * @inheritdoc
     */
    public function constructQuery() {
        $q = $this->query;
        if ($this->where) $q .= " WHERE $this->where";
        if ($this->orderBy) $q .= " ORDER BY $this->orderBy";
        if ($this->limit) $q .= " LIMIT $this->limit";
        return $q;
    } 

    /**
     * Set the "where" clause of the query (do not include the 'WHERE' keyword)
     *
     * @param string $val
     */
    public function setWhere($val) {
        $this->properties['where'] = $val;
        return $this;
    }

    /**
     * Set the "order by" clause of the query (do not include the 'ORDER BY' keyword)
     *
     * @param string $val
     */
    public function setOrderBy($val) {
        $this->properties['orderBy'] = $val;
        return $this;
    }

    /**
     * Set the "limit" clause of the query (do not include the 'LIMIT' keyword)
     *
     * @param string $val
     */
    public function setLimit($val) {
        $this->properties['limit'] = $val;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getParams() {
        return $this->properties['params'];
    }

    /**
     * Set the parameters for this query.
     *
     * This should be an array that can be used in the `\PDO::execute()` method
     *
     * @param array $val
     * @return static
     */
    public function setParams(array $val) {
        $this->properties['params'] = $val;
        return $this;
    }
}

