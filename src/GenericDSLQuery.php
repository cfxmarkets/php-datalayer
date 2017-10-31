<?php
namespace CFX\Persistence;

class GenericDSLQuery implements DSLQueryInterface {
    protected $primaryKey = 'id';
    protected $expressions = [];
    protected $operator = 'and';

    /*

        string = "(name like 'kael%' or email like 'kael%') and dob > 20000101 and dob < 20051231 and bestFriend in ('12345', '67890', '56473')"

        Query(
            Expressions(
                0: Query(
                    Expressions(
                        0: Comparison(
                            field: 'name',
                            operator: 'like',
                            value: 'kael%'
                        ),
                        1: Comparison(
                            field: 'email',
                            operator: 'like',
                            value: 'kael%'
                        )
                    ),
                    Operator: 'OR'
                ),
                1: Comparison(
                    field: 'dob',
                    operator: '>',
                    value: 20000101
                ),
                2: Comparison(
                    field: dob
                    operator: <
                    value: 20051231
                ),
                3: Comparison(
                    field: bestFriend,
                    operator: in
                    value: ['12345','67890','56473']
                )
            ),
            Operator: 'AND'
        )

        Everything is either a 'field', 'operator', 'query', or 'set'

    */

    protected function __construct() {
    }

    public static function parse($q) {
        $query = new static();
        if (!$q) return $query;

        if (preg_match("/[()]/", $q)) {
            throw new BadQueryException("Sorry, grouping is currently not accepted in queries");
        }

        if (strpos($q, " or ") !== false) {
            throw new BadQueryException("Sorry, 'and' is currently the only supported operator in queries");
        }

        $q = explode(" and ", trim($q));

        $fieldList = implode("|",$query::getAcceptableFields());
        $valChars = "[^ &|'\"]";

        foreach($q as $k => $expr) {
            if (preg_match("/^($fieldList) ?(!?=) ?($valChars+)$/i", $expr, $matches)) {
                $setField = "set".ucfirst($matches[1]);
                if (method_exists($query, $setField)) {
                    $query->$setField($matches[3], $matches[2]);
                } else {
                    throw new \RuntimeException(
                        "Programmer: You must implement a `$setField` method for this class (".get_class($query).") ".
                        "in order to successfully parse queries with it."
                    );
                }
            } else {
                throw new BadQueryException(
                    "Unacceptable fields or values found. Acceptable fields are ($fieldList) and ".
                    "values must be alpha-numeric with optional dashes or underscores"
                );
            }
        }

        return $query;
    }

    public function getId() {
        return $this->getExpressionValue('id');
    }

    public function setId($id, $operator) {
        $this->setExpressionValue('id', [
            'field' => $this->primaryKey,
            'operator' => $operator,
            'value' => $id
        ]);
        return $this;
    }

    public function getWhere() {
        $str = [];
        foreach($this->expressions as $name => $expr) {
            if ($expr instanceof DSLQueryInterface) {
                $str[] = (string)$expr;
            } else {
                $str[] = "`$expr[field]` $expr[operator] ?";
            }
        }

        if (count($str) === 0) {
            return null;
        }

        return implode(" $this->operator ", $str);
    }

    public function getParams() {
        $params = [];

        foreach ($this->expressions as $name => $expr) {
            if ($expr instanceof DSLQueryInterface) {
                $params = array_merge($params, $expr->getParams());
            } else {
                $params[] = $expr['value'];
            }
        }

        return $params;
    }

    public function requestingCollection() {
        return (array_key_exists('id', $this->expressions) && $this->expressions['id']['operator'] == '=');
    }

    public function setOperator($str) {
        if (!in_array($str, $this->getLogicalOperators())) {
            throw new BadQueryException("Sorry, `$str` is not an acceptable operator. Acceptable operators for this query are `".implode('`, `', $this->getLogicalOperators())."`.");
        }
        $this->operator = $str;
    }

    protected function setExpressionValue($name, $val) {
        if ($val instanceof DSLQueryInterface) {
            $this->expressions[$name] = $val;
        } elseif (is_array($val)) {
            $keys = array_keys($val);
            sort($keys);
            if (implode(",", $keys) !== 'field,operator,value') {
                throw new BadQueryException(
                    "Programmer: You've passed a bad expression value to this function. Expression values should either be ".
                    "instances of DSLQueryInterface or arrays containing exactly the keys 'field', 'operator', and 'value'."
                );
            }

            if (!in_array($val['operator'], $this::getComparisonOperators())) {
                throw new BadQueryException(
                    "The expression you've provided has an illegal comparison operator, `$val[operator]`. ".
                    "Legal operators are `".implode("`, `", $this::getComparisonOperators())."`."
                );
            }

            $this->expressions[$name] = $val;
        } elseif ($val === null) {
            unset($this->expressions[$name]);
        } else {
            if (gettype($val) === 'object') {
                $type = get_class($val);
            } else {
                $type = gettype($val);
            }

            throw new \RuntimeException("Don't know how to handle values of type `$type` (object string value: `$val`)");
        }

        return $this;
    }

    public function __toString() {
        $str = [];
        foreach($this->expressions as $name => $expr) {
            if ($expr instanceof DSLQueryInterface) {
                $str[] = "(".$expr.")";
            } else {
                $str[] = "$expr[field]$expr[operator]$expr[value]";
            }
        }

        return implode(" $this->operator ", $str);
    }

    protected function getExpressionValue($name) {
        if (array_key_exists($name, $this->expressions)) {
            return $this->expressions[$name]['value'];
        } else {
            return null;
        }
    }

    protected static function getAcceptableFields() {
        return [ 'id' ];
    }

    protected static function getLogicalOperators() {
        return [ 'and', 'or' ];
    }

    protected static function getComparisonOperators() {
        return ['=', '!=', 'like', '>', '<', '>=', '<='];
    }
}

