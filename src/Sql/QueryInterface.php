<?php
namespace CFX\Persistence\Sql;

/**
 * A bare-bones SQL Query abstraction class.
 *
 * This interface is not meant to represent a fully-featured Query builder. It is only meant to provide a uniform
 * interface that can be used to encapsulate the required values for use with a complete PDO query. That is, this
 * interface may be used by any database class whose underlying mechansim is a PDO to first prepare, then execute
 * a valid SQL Query.
 */
interface QueryInterface {
    /**
     * Construct a SQL statement string that may be used in the `\PDO::prepare()` method
     *
     * @return string
     */
    public function constructQuery();

    /**
     * Get the name of the database specified for this query
     *
     * This should be one of the keys pointing to a PDO (or PDO enclosure) in the Context. It should allow
     * the consuming entity to locate the correct PDO to execute this query.
     *
     * @return string
     */
    public function getDatabase();

    /**
     * Get the parameters array for this query.
     *
     * This should return an array that can be used in the `\PDO::execute()` method
     *
     * @return array
     */
    public function getParams();
}

