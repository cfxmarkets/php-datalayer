<?php
namespace CFX\Persistence\Sql;

/**
 * An abstract SQL Datasource
 *
 * This class is meant to represent a single object. For example, in a `PetStore` data context, you might have `IguanaDatasource`,
 * `CatDatasource`, `DogDatasource`, etc. These individual datasources extends from this abstract source, and all contain references
 * to their parent `PetStore` context.
 */
abstract class AbstractDatasource extends \CFX\Persistence\AbstractDatasource implements \CFX\Persistence\DatasourceInterface {
    protected $fieldMap = [];
    protected $primaryKeyName = 'id';
    protected $generatePrimaryKey = true;
    protected $lastInsertId;

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

    protected function saveExisting(\CFX\JsonApi\ResourceInterface $r) {
        $data = $r->getChanges();

        // Initialize inserts
        $fields = [];

        // Add attributes
        $attrs = array_keys($data['attributes']);
        for($i = 0, $ln = count($attrs); $i < $ln; $i++) {
            $column = $this->mapAttribute($attrs[$i], 'column');
            if (!$column) {
                continue;
            }
            $fields[$column] = $data['attributes'][$attrs[$i]];
        }

        // Add relationships
        if (array_key_exists('relationships', $data)) {
            $rels = array_keys($data['relationships']);
            for($i = 0, $ln = count($rels); $i < $ln; $i++) {
                $column = $this->mapRelationship($rels[$i], 'column');
                if (!$column) {
                    continue;
                }

                $val = $data['relationships'][$rels[$i]]->getData();
                $val = $val ? $val->getId() : null;
                $fields[$column] = $val;
            }
        }

        // If there are no changes, just return
        if (count($fields) === 0) {
            return $this;
        }

        $q = $this->newSqlQuery([
            'query' => "UPDATE {$this->getAddress()} SET `".implode("` = ?, `", array_keys($fields))."` = ?",
            'where' => "`{$this->getPrimaryKeyName()}` = ?",
            'params' => array_merge(array_values($fields), [$r->getId()]),
        ]);

        $this->executeQuery($q);

        return $this;
    }

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
        $columns = [];
        $placeholders = [];
        $vals = [];

        // Add ID, if applicable
        if ($this->generatePrimaryKey) {
            $columns[] = $this->getPrimaryKeyName();
            $placeholders[] = '?';
            $vals[] = $r->getId();
        }

        // Add attributes
        $attrs = array_keys($data['attributes']);
        for($i = 0, $ln = count($attrs); $i < $ln; $i++) {
            $column = $this->mapAttribute($attrs[$i], 'column');
            if (!$column) {
                continue;
            }
            $columns[] = $column;
            $placeholders[] = '?';
            $vals[] = $data['attributes'][$attrs[$i]];
        }

        // Add relationships
        if (array_key_exists('relationships', $data)) {
            $rels = array_keys($data['relationships']);
            for($i = 0, $ln = count($rels); $i < $ln; $i++) {
                $column = $this->mapRelationship($rels[$i], 'column');
                if (!$column) {
                    continue;
                }

                $columns[] = $column;
                $placeholders[] = '?';
                $val = $data['relationships'][$rels[$i]]->getData();
                $vals[] = $val ? $val->getId() : null;
            }
        }

        $q = $this->newSqlQuery([
            "query" => "INSERT INTO {$this->getAddress()} (`".implode('`, `', $columns)."`) VALUES (".implode(", ", $placeholders).")",
            "params" => $vals
        ]);

        $this->lastInsertId = $this->executeQuery($q);

        return $this;
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

    protected function getDbName() {
        return null;
    }

    protected function getTableName() {
        return $this->getResourceType();
    }

    protected function getPrimaryKeyName() {
        return $this->primaryKeyName;
    }

    protected function getAddress() {
        $addr = [];
        $dbName = $this->getDbName();
        if ($dbName) {
            $addr[] = $dbName;
        }
        $addr[] = $this->getTableName();
        return "`".implode("`.`", $addr)."`";
    }

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
        }   

        return $name;
    }

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
        }
    }

    protected function executeQuery(QueryInterface $query) {
        return $this->context->executeQuery($query);
    }

    protected function newSqlQuery(array $q=[]) {
        return new Query($q);
    }




    /**
     * Convert data to JsonApi format
     *
     * @param string $type What to put for the `type` parameter
     * @param array $records The rows of data to convert
     * @param array $rels An optional list of relationships. Array should be indexed by FIELD NAME, and each item
     * should be an array whose first value is the `type` of object that the relationship deals with and whose
     * second value is the `name` of the relationship.
     * @param string $idField The name of the field that contains the object's ID (Default: 'id')
     * @return array A collection of resources represented in json api format
     */

    protected function convertToJsonApi($type, $records, $rels=[], $idField='id') {
        if (count($records) == 0) return $records;

        $jsonapi = [];
        foreach($records as $n => $r) {
            $jsonapi[$n] = [
                'type' => $type,
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
}

