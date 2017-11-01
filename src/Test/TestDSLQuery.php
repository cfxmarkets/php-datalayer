<?php
namespace CFX\Persistence\Test;

class TestDSLQuery extends \CFX\Persistence\GenericDSLQuery {
    protected static function getAcceptableFields() {
        return array_merge(parent::getAcceptableFields(), [ 'test1', 'test2' ]);
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
}

