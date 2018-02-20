<?php
namespace CFX\Persistence\Sql;

/**
 * An abstract SQL Datasource
 *
 * This class is meant to represent a single object. For example, in a `PetStore` data context, you might have `IguanaDatasource`,
 * `CatDatasource`, `DogDatasource`, etc. These individual datasources extend from this abstract source, and all contain references
 * to their parent `PetStore` context.
 */
abstract class AbstractDatasource extends \CFX\Persistence\AbstractDatasource implements \CFX\Persistence\DatasourceInterface {
    /**
     * @var A map of resource fields to database column names, used to allow for flexible integration with non-conformant data sources.
     * Fields may optionally map to `null`, in which case they are simply skipped in `get` and `save` methods
     */
    protected $fieldMap = [];

    /**
     * @var string The name of this class's primary key in the database (should usually be 'id', but may be non-conformant)
     */
    protected $primaryKeyName = 'id';

    /**
     * @var string Whether or not to generate our own primary key on save (if the database uses triggers to generate ids, this
     * should be false)
     */
    protected $generatePrimaryKey = true;

    /**
     * @var string|int|null The created by the last insert query
     */
    protected $lastInsertId;

    /**
     * @var array A PDO or Closure that instantiates a PDO (@see __construct())
     */
    private $pdo;





    /**
     * Construct a SQL Datasource
     *
     * @param array $pdo The PDO that provides the SQL connection for this datasource. For example:
     *      function() {
     *          $pdo = new \PDO('mysql:unix_socket=/var/run/mysql/mysql.sock;dbname=exampledb', 'dev', 'dev');
     *          $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
     *          return $pdo;
     *      } 
     */
    public function __construct(DataContextInterface $context, $pdo) {
        $this->pdo = $pdo;
        parent::__construct($context);
    }





    /**
     * A method for returning a specification that can help when translating between JSON-API format and the underlying
     * storage mechanism.
     *
     * At the time of this writing, the intended purpose of this is to define the relationships of an object such that they
     * may be easily converted from raw data to jsonapi format. (This will likely eventually replace `$fieldMap` as a way
     * of mapping fields to database columns as well).
     *
     * @return array An arbitrary array 
     */
    abstract public function getObjectSpec();




    /**
     * @inheritdoc
     */
    public function delete($r) {
        if (!is_string($r) && !is_int($r) && (!is_object($r) || !($r instanceof \CFX\JsonApi\ResourceInterface))) {
            $type = gettype($r);
            if ($type === 'object') {
                $type = "`".get_class($r)."`";
            } else {
                $type = "`$type($r)`";
            }
            throw new \InvalidArgumentException("You must pass either a string ID or a Resource into this function. (You passed $type.)");
        }

        if (is_object($r)) $r = $r->getId();

        if ($r === null) throw new \CFX\UnidentifiedResourceException("Can't delete resource because the resource you're trying to delete doesn't have an ID.");

        $this->executeQuery($this->newSqlQuery([
            'query' => "DELETE FROM {$this->getAddress()}",
            'where' => "`{$this->getPrimaryKeyName()}` = ?",
            'params' => [$r],
        ]));

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function saveExisting(\CFX\JsonApi\ResourceInterface $r) {
        $data = $r->getChanges();

        // Initialize inserts
        $q = [
            'cols' => [],
            'expressions' => [],
            'vals' => [],
        ];

        // Add attributes
        $attrs = array_keys($data['attributes']);
        for($i = 0, $ln = count($attrs); $i < $ln; $i++) {
            $column = $this->mapAttribute($attrs[$i], 'column');
            if (!$column) {
                continue;
            }
            $q['cols'][] = $column;
            $q['expressions'][] = '?';
            $q['vals'][] = $this->getParamValue($attrs[$i], $data, $r);
        }

        // Add relationships
        if (array_key_exists('relationships', $data)) {
            $rels = array_keys($data['relationships']);
            for($i = 0, $ln = count($rels); $i < $ln; $i++) {
                $column = $this->mapRelationship($rels[$i], 'column');
                if (!$column) {
                    continue;
                }
                $q['cols'][] = $column;
                $q['expressions'][] = '?';
                $q['vals'][] = $this->getParamValue($rels[$i], $data, $r);
            }
        }

        // If there are no changes, just return
        if (count($q['cols']) === 0) {
            return $this;
        }

        $q = $this->adjustFinalQueryParams($q);
        $set = [];
        foreach($q['cols'] as $i => $column) {
            $set[] = "`$column` = ".$q['expressions'][$i];
        }

        $q = $this->newSqlQuery([
            'query' => "UPDATE {$this->getAddress()} SET ".implode(", ", $set),
            'where' => "`{$this->getPrimaryKeyName()}` = ?",
            'params' => array_merge($q['vals'], [$r->getId()]),
        ]);

        $this->executeQuery($q);

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function saveNew(\CFX\JsonApi\ResourceInterface $r) {
        if ($this->generatePrimaryKey) {
            $r->setId(md5(uniqid()));
            if (!$r->getId()) {
                throw new \RuntimeException(
                    "Looks like the `id` field for this resource is still set to read-only. You should be add the `\CFX\JsonApi\PrivateResourceTrait` ".
                    "to any private resources you create to allow them to set the ID field."
                );
            }
        }

        $data = $r->jsonSerialize();

        // Initialize inserts
        $q = [
            'cols' => [],
            'expressions' => [],
            'vals' => [],
        ];

        // Add ID, if applicable
        if ($this->generatePrimaryKey) {
            $q['cols'][] = $this->getPrimaryKeyName();
            $q['expressions'][] = '?';
            $q['vals'][] = $r->getId();
        }

        // Add attributes
        $attrs = array_keys($data['attributes']);
        for($i = 0, $ln = count($attrs); $i < $ln; $i++) {
            $column = $this->mapAttribute($attrs[$i], 'column');
            if (!$column) {
                continue;
            }
            $q['cols'][] = $column;
            $q['expressions'][] = '?';
            $q['vals'][] = $this->getParamValue($attrs[$i], $data, $r);
        }

        // Add relationships
        if (array_key_exists('relationships', $data)) {
            $rels = array_keys($data['relationships']);
            for($i = 0, $ln = count($rels); $i < $ln; $i++) {
                $column = $this->mapRelationship($rels[$i], 'column');
                if (!$column) {
                    continue;
                }

                $q['cols'][] = $column;
                $q['expressions'][] = '?';
                $q['vals'][] = $this->getParamValue($rels[$i], $data, $r);
            }
        }

        $q = $this->adjustFinalQueryParams($q);

        $q = $this->newSqlQuery([
            "query" => "INSERT INTO {$this->getAddress()} (`".implode('`, `', $q['cols'])."`) VALUES (".implode(", ", $q['expressions']).")",
            "params" => $q['vals']
        ]);

        $this->lastInsertId = $this->executeQuery($q);

        return $this;
    }

    /**
     * A before-query hook for save queries
     *
     * This hook allows programmers to adjust final query parameters before insert or update, effectively allowing them to arbitrarily
     * add to, subtract from, or completely rewrite queries before they're actually run.
     *
     * @param string[][] $params An array containing the columns, placeholders ("expressions"), and values of the query. This array should have
     * EXACTLY the keys `cols`, `expressions`, and `vals`, which should each be a regular, integer-indexed array of string values. Furthermore,
     * `expressions` may contain one or more question marks marking placeholders for values.
     * @return string[][] Should return an array of the same format that was received
     */
    protected function adjustFinalQueryParams(array $params)
    {
        return $params;
    }

    /**
     * getLastInsertId -- Get the auto-generated id of the last insert
     *
     * @return string|int|null The last insert id
     */
    public function getLastInsertId()
    {
        return $this->lastInsertId;
    }

    /**
     * Get the database name of the table for this resource (usually null, but may be overridden for transitional datasources)
     * @return string|null
     */
    protected function getDbName() {
        return null;
    }

    /**
     * Get the name of the table for this resource
     * @return string
     */
    protected function getTableName() {
        return $this->getResourceType();
    }

    /**
     * Get this resource's primary key name
     * @deprecated Use $this->primaryKeyName instead
     * @return string
     */
    protected function getPrimaryKeyName() {
        return $this->primaryKeyName;
    }

    /**
     * Get the "address" of the data for this datasource. An "address" consists of the escaped table name, optionally preceded
     * by the escaped database name, if database name is provided by this class.
     *
     * @return string
     */
    protected function getAddress() {
        $addr = [];
        $dbName = $this->getDbName();
        if ($dbName) {
            $addr[] = $dbName;
        }
        $addr[] = $this->getTableName();
        return "`".implode("`.`", $addr)."`";
    }

    /**
     * Map the name of an attribute in the Resource class to the name of a column in the database, or vice versa
     *
     * @param string $name The name to map
     * @param 'column'|'field' $to What to map the name to
     */
    protected function mapAttribute($name, $to)
    {   
        if ($to === 'column') {
            if (array_key_exists($name, $this->fieldMap)) {
                return $this->fieldMap[$name];
            }   
        } elseif ($to === 'field') {
            if (($field = array_search($name, $this->fieldMap)) !== false) {
                return $field;
            }   
        } else {
            throw new \RuntimeException("You must specify either 'column' or 'field' as the \$to value for this function. (You specified `$to`.)");
        }

        return $name;
    }

    /**
     * Map the name of a relationship in the Resource class to the name of a column in the database, or vice versa
     *
     * @param string $rel The name to map
     * @param 'column'|'field' $to What to map the name to
     */
    protected function mapRelationship($rel, $to) {
        if ($to === 'column') {
            if (array_key_exists($rel, $this->fieldMap)) {
                return $this->fieldMap[$rel];
            }   
            return "{$rel}Id";
        } elseif ($to === 'field') {
            if (($field = array_search($rel, $this->fieldMap)) !== false) {
                return $field;
            }   
            if (strtolower(substr($rel, -2)) === 'id') {
                return substr($rel, 0, -2);
            } else {
                return $rel;
            }
        } else {
            throw new \RuntimeException("You must specify either 'column' or 'field' as the \$to value for this function. (You specified `$to`.)");
        }
    }

    /**
     * Get the database value for the given field
     *
     * $data is primarily used, but the Resource parameter may also be used by derivative method
     * implementations to compose complex values.
     *
     * @param string $field The field whose value to return
     * @param array $data The object's data in jsonapi format
     * @param \CFX\JsonApi\ResourceInterface $r The resource, just in case
     * @return mixed The database value for `$field`
     */
    protected function getParamValue($field, array $data, \CFX\JsonApi\ResourceInterface $r)
    {
        if ($field === $this->primaryKeyName) {
            if (array_key_exists('id', $data)) {
                return $data['id'];
            } else {
                return null;
            }
        } elseif (array_key_exists($field, $data['attributes'])) {
            return $data['attributes'][$field];
        } elseif (array_key_exists('relationships', $data) && array_key_exists($field, $data['relationships'])) {
            $val = $data['relationships'][$field]->getData();
            if ($val) {
                if ($val->getId()) {
                    return $val->getId();
                } else {
                    throw new \RuntimeException("Programmer: You've passed an object of type `{$val->getResourceType()}` to be saved that doesn't have an id.");
                }
            } else {
                return null;
            }
        }
    }

    /**
     * Execute the given SQL Query
     *
     * @param QueryInterface $query
     * @return array|string|int|null Returns the result set on SELECT queries, or the last insert id (if applicable) on other queries
     */
    protected function executeQuery(QueryInterface $query) {
        $q = $this->getPdo($query->database)->prepare($query->constructQuery());
        $q->execute($query->params);

        // Log query
        $this->context->logQuery("SQL Datasource `{$this->getResourceType()}`", $query->constructQuery()." (".var_export($query->params, true).")");

        if ($q->columnCount() > 0) {
            return $q->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            return $this->getPdo($query->database)->lastInsertId();
        }
    }

    /**
     * Factory method for creating a new SQl Query object
     *
     * This method allows datasources to implement specialized SQL Query extensions
     *
     * @param array $q The parameters for the new sql query. @see \CFX\Persistence\Sql\Query for information.
     * @return \CFX\Persistence\Sql\QueryInterface
     */
    protected function newSqlQuery(array $q=[]) {
        return new Query($q);
    }




    /**
     * Convert data to JsonApi format
     *
     * @param array $records The rows of data to convert
     * @return array A collection of resources represented in json api format
     */

    protected function convertToJsonApi($records) {
        if (count($records) == 0) {
            return $records;
        }

        $spec = $this->getObjectSpec();
        if (array_key_exists('primaryKey', $spec)) {
            $idField = $spec['primaryKey'];
        } else {
            $idField = 'id';
        }

        $rels = [];
        if (array_key_exists('relationships', $spec)) {
            foreach($spec['relationships'] as $field => $info) {
                if (!$info) {
                    continue;
                }
                $rels[$info[0]] = [ $info[1], $field ];
            }
        }

        $jsonapi = [];
        foreach($records as $n => $r) {
            $jsonapi[$n] = [
                'type' => $this->getResourceType(),
                'id' => $r[$idField],
                'attributes' => [],
            ];
            if (count($rels) > 0) $jsonapi[$n]['relationships'] = [];

            foreach ($r as $field => $v) {
                if ($field == $idField) continue;
                if (array_key_exists($field, $rels)) {
                    // For to-many relationships
                    if (is_array($v)) {
                        $rel = [];
                        foreach($v as $relId) {
                            $rel[] = [
                                "data" => [
                                    "type" => $rels[$field][0],
                                    "id" => $relId,
                                ],
                            ];
                        }

                    // For to-one relationships
                    } else {
                        if (!$v) $rel = [ "data" => null ];
                        else $rel = [
                            "data" => [
                                "type" => $rels[$field][0],
                                "id" => $v,
                            ]
                        ];
                    }

                    // Add relationship
                    $jsonapi[$n]['relationships'][$rels[$field][1]] = $rel;
                } else {
                    $jsonapi[$n]['attributes'][$field] = $v;
                }
            }
        }

        return $jsonapi;
    }


    /**
     * Gets the PDO for this datasource, instantiating if necessary
     *
     * @return \PDO
     * @throws \RuntimeException
     */
    protected function getPdo() {
        if ($this->pdo instanceof \Closure) {
            $pdo = $this->pdo;
            $this->pdo = $pdo();
        }
        if ($this->pdo instanceof \PDO || $this->pdo instanceof \CFX\Test\PDO) {
            return $this->pdo;
        }

        $type = is_object($this->pdo) ? get_class($this->pdo) : gettype($this->pdo)." (".$this->pdo.")";
        throw new \RuntimeException("Programmer: The PDO you pass to a \\CFX\\Sql\\Datasource must either be a Closure that returns a valid PDO or an already-instantiated PDO. You've passed an object which is neither (`$type`).");
    }
}

