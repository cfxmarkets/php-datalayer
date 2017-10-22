<?php
namespace CFX\Persistence;

class GenericDSLQuery implements DSLQueryInterface {
    protected $primaryKey = 'id';
    protected $expressions;
    protected $operator;
    protected $where;
    protected $params = [];
    protected $requestingCollection = true;

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
                3: Relationship(
                    field: bestFriend,
                    operator: in
                    set: ['12345','67890','56473']
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

        if (substr($q, 0, 3) == 'id=') {
            $query->where = "`$query->primaryKey` = ?";
            $query->params = [substr($q, 3)];
            $query->requestingCollection = false;
        } else {
            throw new UnimplementedFeatureException("Sorry, we don't yet support queries beyond `id=....`");
        }
        return $query;
    }

    public function getWhere() {
        return $this->where;
    }

    public function getParams() {
        return $this->params;
    }

    public function requestingCollection() {
        return $this->requestingCollection;
    }
}

