<?php

use \CFX\JsonApi\Meta;

class MetaTest extends \PHPUnit\Framework\TestCase {
    public function testInstantiatesCorrectly() {
        $m = new Meta();
        $this->assertTrue($m instanceof \CFX\JsonApi\MetaInterface);

        $m = new Meta([
            'test-object' => [
                'test1' => 1,
                'test2' => 'two',
                'test3' => [
                    'test-a' => 'a',
                    'test-b' => 'b',
                ]
            ],
            'test-prop' => 'prop',
        ]);
        $this->assertTrue($m instanceof \CFX\JsonApi\MetaInterface);
    }

    public function testInterface() {
        $m = new Meta();
        $m['test-object'] = new Meta();
        $m['test-value'] = 'prop1';
        $m['test-array'] = [
            'item1',
            'item2',
            'item3',
        ];

        $this->assertTrue($m['test-object'] instanceof \CFX\JsonApi\MetaInterface);
        $this->assertEquals('prop1', $m['test-value']);
        $this->assertEquals('item2', $m['test-array'][1]);
    }

    public function testSerializesCorrectly() {
        $m = new Meta([
            'test-object' => [
                'test1' => 1,
                'test2' => 'two',
                'test3' => [
                    'test-a' => 'a',
                    'test-b' => 'b',
                ],
                'test4' => [
                    'item1',
                    'item2',
                ],
            ],
            'test-prop' => 'prop',
        ]);

        $this->assertEquals('{"test-object":{"test1":1,"test2":"two","test3":{"test-a":"a","test-b":"b"},"test4":["item1","item2"]},"test-prop":"prop"}', json_encode($m));
    }
}

