<?php
namespace CFX\Persistence;

interface DSLQueryInterface {
    /**
     * Parse the given string query and return the resulting DSLQueryInterface
     *
     * @param string $query The DSL query string to parse
     * @return \CFX\Persistence\DSLQueryInterface
     * @throws \CFX\Persistence\BadQueryException
     */
    public static function parse($query);

    /**
     * Get the Id of the query, if set
     *
     * @return string|int|null
     */
    public function getId();

    /**
     * Set the Id of the query
     *
     * Sets the "id" field of the query.
     *
     * @param string $operator The operator for the id field (usually "=" or "!=")
     * @param string|int $id The id
     * @return static
     */
    public function setId($operator, $id);

    /**
     * Removes the ID field from the query
     *
     * @return static
     */
    public function unsetId();

    /**
     * Get the SQL "WHERE" clause that this query represents
     *
     * (NOTE: This should be moved into a SQL-specific derivative)
     *
     * @return string
     */
    public function getWhere();

    /**
     * Get an array with all of the parameters for this query, prepared for a PDO "execute" statement
     *
     * (NOTE: This should be moved into a SQL-specific derivative)
     *
     * @return string
     */
    public function getParams();

    /**
     * Returns true of this query represents a request for a collection of items, or false otherwise
     *
     * @return bool
     */
    public function requestingCollection();

    /**
     * Sets the operator of the Query.
     *
     * The general idea is that a given query can be composed of several subqueries, but each query or subquery may have only
     * one operator ("and" or "or"). This is the method used to set that operator.
     *
     * @param string $operator
     * @return static
     */
    public function setOperator($operator);

    /**
     * Should return true if the named field is among the expressions AND the operator
     * for the field is '=' AND the operator for the query is 'and' (not 'or')
     *
     * @param string $name The name of the field to check
     * @return bool
     */
    public function includes($name);

    /**
     * Turns the query into a parsable DSL string
     *
     * @return string
     */
    public function __toString();
}

