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
    protected $properties = [ 'database', 'query', 'where', 'sort', 'limit', 'params' ];

    /**
     * Construct a new SQL Query object
     *
     * @param array $opts The properties of this query (must contain only the properties listed in `-$this->properties`
     * @return static
     */
    public function __construct(array $opts) {
        $properties = [];

        // Make sure that passed in params are acceptable
        foreach($opts as $prop => $val) {
            if (!in_array($prop, $this->properties)) throw new \RuntimeException("Property not supported: `$prop`");
            $properties[$prop] = $val;
        }

        // Set defaults for parameters not passed in
        foreach($this->properties as $p) {
            if (!array_key_exists($p, $properties)){
                if ($p === "params") {
                    $properties[$p] = [];
                } else {
                    $properties[$p] = null;
                }
            }
            $this->$p = $properties[$p];
        }

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
     * Static method for converting unknown pagination schemes into a valid "limit" string
     *
     * @param array $pagination An array representing some sort of pagination scheme
     * @return string A string compatible with the SQL LIMIT clause
     *
     * @throws \CFX\Persistence\BadQueryException
     */
    public static function getLimitFromPagination(array $pagination)
    {
        // For now, ONLY supporting page number/size pagination, not cursor or other
        $incompatibleKeys = array_diff(array_keys($pagination), [ "number", "size" ]);
        if (count($incompatibleKeys) !== 0) {
            throw new \CFX\Persistence\BadQueryException(
                "For now, this system only supports pagination with 'number' and 'size' keys. You've passed ".
                "the following incompatible keys: ".implode(",", $incompatibleKeys)
            );
        }

        // It's an error not to specify an integer 'size' parameter
        if (
            !isset($pagination["size"]) ||
            !is_numeric($pagination["size"]) ||
            (int)$pagination["size"] != $pagination["size"]
        ) {
            throw new \CFX\Persistence\BadQueryException(
                "You MUST specify an integer 'size' parameter that defines how many records appear per page."
            );
        }

        $limit = $pagination["size"];
        if (isset($pagination["number"])) {
            if ((int)$pagination["number"] != $pagination["number"]) {
                throw new \CFX\Persistence\BadQueryException(
                    "If you specify a 'number' parameter, it MUST be an integer."
                );
            }

            $limit = ($pagination["number"] * $limit).", $limit";
        }

        return $limit;
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
    public function setDatabase(?string $val) {
        if (strpos($val, "--") !== false) {
            throw new \CFX\Persistence\BadQueryException("Database name may not have '--' in it.");
        }
        $this->properties['database'] = $val;
        return $this;
    }

    /**
     * Set the base query.
     *
     * Because this is a very free-form class, the "query" parameter should contain everything up to the "WHERE" clause. For example,
     * a valid value for this might be, `SELECT val1, val2, val3 FROM somedb.sometable`
     *
     * @param string $val
     * @return static
     */
    public function setQuery(?string $val) {
        if (strpos($val, "--") !== false) {
            throw new \CFX\Persistence\BadQueryException("Query may not have '--' in it.");
        }
        $this->properties["query"] = $val;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function constructQuery() {
        $q = $this->query;
        if ($this->where) $q .= " WHERE $this->where";
        if ($this->sort) $q .= " ORDER BY $this->sort";
        if ($this->limit) $q .= " LIMIT $this->limit";
        return $q;
    } 

    /**
     * Set the "where" clause of the query (do not include the 'WHERE' keyword)
     *
     * @param string $val
     */
    public function setWhere(?string $val) {
        if (strpos($val, "--") !== false) {
            throw new \CFX\Persistence\BadQueryException("'WHERE' clause may not have '--' in it.");
        }
        $this->properties['where'] = $val;
        return $this;
    }

    /**
     * Set the "sort" clause of the query (input should conform to [JSON:API sort syntax](https://jsonapi.org/format/#fetching-sorting))
     *
     * E.g., `GET /posts?sort=-datePublished,author.lastName` would result in something like 'ORDER BY datePublished DESC, a.lastName ASC'
     * in the final query.
     *
     * @param string $val
     * @return static
     */
    public function setSort(?string $val) {
        if (!$val) {
            $this->properties["sort"] = null;
            return $this;
        }

        $sortString = [];

        // For each field specified, get its mapped query-specific field name and then use the optional
        // +/- prefix to specify the sort order
        foreach(explode(",", $val) as $sortParam) {
            if (!preg_match("/^([+-]?)([a-z0-9_-]+)$/i", $sortParam, $paramInfo)) {
                throw new \CFX\Persistence\BadQueryException(
                    "Sort parameters must match [JSON:API sort syntax](https://jsonapi.org/format/#fetching-sorting). ".
                    "E.g., /posts?sort=-datePublished,author.lastName"
                );
            }

            $field = $this->getQueryFieldName($paramInfo[2]);

            // Sort is _ascending_ by default
            $order = ($paramInfo[1] === '-') ? "DESC" : "ASC";
            $sortString[] = "$field $order";
        }

        $this->properties['sort'] = implode(",", $sortString);
        return $this;
    }

    /**
     * Set the "limit" clause of the query (do not include the 'LIMIT' keyword)
     *
     * @param string $val
     */
    public function setLimit(?string $val) {
        if ($val && !preg_match("/^(?:(?:[0-9]+, ?)?[0-9]+)|(?:[0-9]+ +OFFSET +[0-9]+)$/i", $val)) {
            throw new \CFX\Persistence\BadQueryException(
                "LIMIT clause must be of the format '([offset], )[limit]' or '[limit]( OFFSET [offset])'. You've passed '$val'."
            );
        }
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

    /**
     * Get the field name that the current specific query is expecting to see, as derived from the input
     * in JSON:API dot-separated notation (e.g., `author.lastName`)
     *
     * This function is intended to be overridden by specific queries that need more specific addressing
     * of fields.
     *
     * @param string $dotField The dot-separated field name (e.g., `author.lastName`)
     * @return string The query-specific SQL field address (e.g., `my-authors-table.lastName`)
     */
    protected function getQueryFieldName(string $dotField)
    {
        $fields = explode(".", $dotField);
        $lastField = array_pop($fields);
        return $lastField;
    }
}

