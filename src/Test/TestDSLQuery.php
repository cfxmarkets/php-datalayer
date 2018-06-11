<?php
namespace CFX\Persistence\Test;

class TestDSLQuery extends \CFX\Persistence\GenericDSLQuery {
    protected static function getAcceptableFields() {
        return array_merge(parent::getAcceptableFields(), [ 'test1', 'test2', 'test3' ]);
    }

    protected static function getComparisonOperators()
    {
        return array_merge(parent::getComparisonOperators(), [ "in" ]);
    }

    protected static function getFieldValueSpecification()
    {
        return "(\(?['\"]?.+?['\"]?(?:, ?)?\)?)";
    }

    public function setTest1($operator, $val) {
        $this->setExpressionValue('test1', [
            'field' => 'test1',
            'operator' => $operator,
            'value' => $val
        ]);
    }

    public function getTest1() {
        return $this->getExpressionValue('test1');
    }

    public function setTest2($operator, $val) {
        $this->setExpressionValue('test2', [
            'field' => 'test2',
            'operator' => $operator,
            'value' => $val
        ]);
    }

    public function getTest2() {
        return $this->getExpressionValue('test2');
    }

    public function setTest3($operator, $val)
    {
        $expressionVal = [
            'field' => 'test3',
            'operator' => "in",
            "value" => $val,
        ];

        if ($operator === "in" || $operator === "not in") {
            $valList = preg_split("/, ?/", trim($val, " ()"));
            $valList = array_map(function($v) { return trim($v, "'\""); }, $valList);
            $expressionVal["value"] = $valList;
        }

        $this->setExpressionValue('test3', $expressionVal);
    }

    public function getTest3()
    {
        return $this->getExpression("test3");
    }
}

