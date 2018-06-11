<?php
namespace CFX\Persistence;

class GenericDSLQuery implements DSLQueryInterface {
    protected $primaryKey = 'id';
    protected $expressions = [];
    protected $operator = 'and';

    /*

        Ideas for future implementation:

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


    /**
     * @inheritdoc
     */
    public static function parse($q) {
        $query = new static();
        if (!$q) return $query;

        if (strpos($q, " or ") !== false) {
            throw new BadQueryException("Sorry, 'and' is currently the only supported operator in queries");
        }

        $q = explode(" and ", trim($q));

        $fieldList = implode("|",$query::getAcceptableFields());
        $valSpec = $query::getFieldValueSpecification();
        $comparison = implode("|", $query::getComparisonOperators());

        foreach($q as $k => $expr) {
            if (preg_match("/^($fieldList) ?($comparison) ?$valSpec$/i", $expr, $matches)) {
                $setField = "set".ucfirst($matches[1]);
                if (method_exists($query, $setField)) {
                    $val = trim($matches[3], "'\"");
                    $query->$setField($matches[2], $val);
                } else {
                    throw new \RuntimeException(
                        "Programmer: You must implement a `$setField` method for this class (".get_class($query).") ".
                        "in order to successfully parse queries with it."
                    );
                }
            } else {
                throw new BadQueryException(
                    "Unacceptable fields or values found. Acceptable fields are ($fieldList) and ".
                    "values must be alpha-numeric with optional dashes or underscores. Offending expression: `$expr`"
                );
            }
        }

        return $query;
    }

    /**
     * @inheritdoc
     */
    public function getId() {
        return $this->getExpressionValue('id');
    }

    /**
     * @inheritdoc
     */
    public function setId($operator, $id) {
        return $this->setExpressionValue('id', [ 'field' => $this->primaryKey, 'operator' => $operator, 'value' => $id ]);
    }

    /**
     * @inheritdoc
     */
    public function unsetId()
    {
        return $this->setExpressionValue('id', null);
    }

    /**
     * @inheritdoc
     */
    public function getWhere() {
        $str = [];
        foreach($this->expressions as $name => $expr) {
            if ($expr instanceof DSLQueryInterface) {
                $str[] = (string)$expr;
            } else {
                $v = '';
                if (array_key_exists('db', $expr)) {
                    $v .= "`$expr[db]`.";
                }
                if (array_key_exists('table', $expr)) {
                    $v .= "`$expr[table]`.";
                }
                if (array_key_exists('options', $expr) && array_key_exists('no-quote', $expr['options'])) {
                    $v .= $expr['field'];
                } else {
                    $v .= "`$expr[field]`";
                }
                $v .= " $expr[operator] ";
                if (is_array($expr["value"])) {
                    $v .= "(".implode(", ", array_map(function($member) { return "?"; }, $expr["value"])).")";
                } else {
                    $v .= "?";
                }
                $str[] = $v;
            }
        }

        if (count($str) === 0) {
            return null;
        }

        return implode(" $this->operator ", $str);
    }

    /**
     * @inheritdoc
     */
    public function getParams() {
        $params = [];

        foreach ($this->expressions as $name => $expr) {
            if ($expr instanceof DSLQueryInterface) {
                $params = array_merge($params, $expr->getParams());
            } else {
                if (is_array($expr['value'])) {
                    $params = array_merge($params, $expr['value']);
                } else {
                    $params[] = $expr['value'];
                }
            }
        }

        return $params;
    }

    /**
     * @inheritdoc
     */
    public function requestingCollection() {
        return !$this->includes('id');
    }

    /**
     * @inheritdoc
     */
    public function setOperator($str) {
        if (!in_array($str, $this->getLogicalOperators())) {
            throw new BadQueryException("Sorry, `$str` is not an acceptable operator. Acceptable operators for this query are `".implode('`, `', $this->getLogicalOperators())."`.");
        }
        $this->operator = $str;
    }

    /**
     * @inheritdoc
     */
    public function includes($name) {
        return
            array_key_exists($name, $this->expressions) &&
            $this->expressions[$name]['operator'] == '='
        ;
    }

    /**
     * @inheritdoc
     */
    public function __toString() {
        $str = [];
        foreach($this->expressions as $name => $expr) {
            if ($expr instanceof DSLQueryInterface) {
                $str[] = "(".$expr.")";
            } else {
                $v = "$name$expr[operator]";
                if (is_array($expr["value"])) {
                    $v .= "('".implode("', '", $expr["value"])."')";
                } else {
                    $v .= $expr["value"];
                }
                $str[] = $v;
            }
        }

        return implode(" $this->operator ", $str);
    }





    /**
     * A method for setting expression values
     *
     * @param string $name An arbitrary name for the expression (e.g., "id", "creator", "entity", etc.)
     * @param array $val An array of expression parameters, including the following:
     *      * string 'field' The actual field name (e.g., "id", "creatorId", "legalEntityId", etc.)
     *      * string $operator The operator of the expression (e.g., "=", "!=")
     *      * string $value The value part of the expression (e.g., 'abc123')
     * @return static
     * @throws \CFX\Persistence\BadQueryException on bad parameters
     */
    protected function setExpressionValue($name, $val) {
        if ($val instanceof DSLQueryInterface) {
            $this->expressions[$name] = $val;
        } elseif (is_array($val)) {
            if (count(array_diff(['field','operator','value'], array_keys($val))) !== 0) {
                throw new BadQueryException(
                    "Programmer: You've passed a bad expression value to this function. Expression values should either be ".
                    "instances of DSLQueryInterface or arrays containing at least the keys 'field', 'operator', and 'value'."
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

    /**
     * Get the value of the given expression (will either be null or an array containing at minimum the
     * fields defined in `setExpressionValue`
     *
     * @param string $name The name of the expression to get
     * @return array|null
     */
    protected function getExpressionValue($name) {
        if (array_key_exists($name, $this->expressions)) {
            return $this->expressions[$name]['value'];
        } else {
            return null;
        }
    }

    /**
     * Return all valid field names for this DSL
     * 
     * @return string[]
     */
    protected static function getAcceptableFields() {
        return [ 'id' ];
    }

    /**
     * Return all valid logical operators for this DSL
     *
     * Used to validate values for `setOperator`
     *
     * @return string[]
     */
    protected static function getLogicalOperators() {
        return [ 'and', 'or' ];
    }

    /**
     * Return all valid comparison operators for this DSL
     *
     * Used to validate values for the `operator` field in an expression value array
     *
     * @return string[]
     */
    protected static function getComparisonOperators() {
        return [ '>=', '<=', '!=', '=', 'like', '>', '<' ];
    }

    /**
     * Return a regex that defines a valid field value specification
     *
     * Note: This method is currently very primordial. It should eventually be expanded to provide a map
     * of field names to value specifications.
     *
     * @return string (regexp capture expression)
     */
    protected static function getFieldValueSpecification() {
        return "['\"]?([^ &'\"]+)['\"]?";
    }
}

