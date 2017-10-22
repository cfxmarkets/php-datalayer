<?php

use \CFX\JsonApi\Test\User;
use \CFX\JsonApi\Test\Datasource;

class ResourceTest extends \PHPUnit\Framework\TestCase {
    public function testCanCreateEmptyResource() {
        $datasource = new Datasource();
        $t = new User($datasource);
        $this->assertTrue($t instanceof \CFX\JsonApi\ResourceInterface, "Should instantiate a valid User object");
    }

    public function testCanCreateValidResource() {
        $data = [
            'type' => 'test-users',
            'attributes' => [
                'name' => 'Jim Chavo',
                'dob' => '12345',
            ],
            'relationships' => [
                'friends' => [
                    'data' => [
                        [
                            'type' => 'test-users',
                            'id' => '1',
                        ],
                        [
                            'type' => 'test-users',
                            'id' => '2',
                        ],
                    ],
                ],
                'boss' => [
                    'data' => [
                        'type' => 'test-users',
                        'id' => '3',
                    ],
                ],
            ],
        ];

        $datasource = new Datasource();

        $t = new \CFX\JsonApi\Test\User($datasource, $data);

        $this->assertTrue($t instanceof \CFX\JsonApi\ResourceInterface);
        $this->assertTrue($t->getFriendsRelationship() instanceof \CFX\JsonApi\RelationshipInterface);
        $this->assertTrue($t->getBossRelationship() instanceof \CFX\JsonApi\RelationshipInterface);
        $this->assertTrue($t->getFriends() instanceof \CFX\JsonApi\ResourceCollectionInterface);
        $this->assertTrue($t->getBoss() instanceof \CFX\JsonApi\ResourceInterface);
        $this->assertEquals('Jim Chavo', $t->getName());
        $this->assertEquals('12345', $t->getDob());
    }

    public function testCanUpdateResourceFromUserInput() {
        $data = [
            'type' => 'test-users',
            'attributes' => [
                'name' => 'Jim Chavo',
                'dob' => '12345',
            ],
            'relationships' => [
                'friends' => [
                    'data' => [
                        [
                            'type' => 'test-users',
                            'id' => '1',
                        ],
                        [
                            'type' => 'test-users',
                            'id' => '2',
                        ],
                    ],
                ],
                'boss' => [
                    'data' => [
                        'type' => 'test-users',
                        'id' => '3',
                    ],
                ],
            ],
        ];

        $datasource = new Datasource();

        $t = new \CFX\JsonApi\Test\User($datasource, $data);

        $this->assertEquals('Jim Chavo', $t->getName(), "Name should be Jim Chavo");
        $this->assertEquals('12345', $t->getDob(), "DOB should be 12345");
        $this->assertEquals(2, count($t->getFriends()), "Should have 2 friends");
        $this->assertEquals('1', $t->getFriends()[0]->getId(), "First friend should be id 1");
        $this->assertEquals('3', $t->getBoss()->getId(), "Boss should be id 3");

        try {
            $t->updateFromData([
                'type' => 'test-not-users',
                'attributes' => [ 'name' => 'John Chavo' ]
            ]);
            $this->fail("Should have thrown an error on mismatched type");
        } catch (\InvalidArgumentException $e) {
            $this->assertTrue(true, "This is the expected behavior");
        }

        $t->updateFromData([
            'type' => 'test-users',
            'attributes' => [ 'name' => 'John Chavo' ]
        ]);

        $this->assertEquals('John Chavo', $t->getName(), "Name should now be John Chavo");
        $this->assertEquals('12345', $t->getDob(), "DOB should still be 12345");

        $t->updateFromData([
            'type' => 'test-users',
            'relationships' => [
                'friends' => [
                    'data' => [
                        [
                            'type' => 'test-users',
                            'id' => '2',
                        ]
                    ]
                ],
                'boss' => [
                    'data' => null
                ]
            ]
        ]);

        $this->assertEquals(1, count($t->getFriends()), "Should have 1 friend");
        $this->assertEquals('2', $t->getFriends()[0]->getId(), "Friend should be id 2");
        $this->assertNull($t->getBoss(), "Boss should be null");
    }

    public function testThrowsExceptionOnBadData() {
        try {
            new \CFX\JsonApi\Test\User(new \CFX\JsonApi\Test\Datasource(), [ 'id' => '12345', 'type' => 'test-users', 'invalid' => 'extra!!!' ]);
            $this->fail("Should have thrown an exception");
        } catch(\CFX\JsonApi\MalformedDataException $e) {
            $this->assertContains("`invalid`", $e->getMessage());
            $this->assertEquals("Resource (`test-users`)", $e->getOffender());
            $this->assertEquals(['invalid'=>'extra!!!'], $e->getOffendingData());
        }
    }

    /*
     * Can't use these in php5.4
    public function testThrowsErrorOnInvalidAttribute() {
        $data = [
            'type' => 'test-users',
            'attributes' => [
                'name' => 'Jim Chavo',
                'dob' => '12345',
                'invalidAttr' => 'nope',
            ],
        ];

        $datasource = new Datasource();

        try {
            $t = new \CFX\JsonApi\Test\User($datasource, $data);
            $this->fail("Should have thrown an exception");
        } catch (\Error $e) {
            $this->assertContains("undefined method", $e->getMessage());
        }
    }

    public function testThrowsErrorOnInvalidRelationship() {
        $data = [
            'type' => 'test-users',
            'attributes' => [
                'name' => 'Jim Chavo',
                'dob' => '12345',
            ],
            'relationships' => [
                'friends' => [
                    'data' => [
                        [
                            'type' => 'test-users',
                            'id' => '1',
                        ],
                    ],
                ],
                'boss' => [
                    'data' => [
                        'type' => 'test-users',
                        'id' => '3',
                    ],
                ],
                'invalidRel' => [
                    'data' => [
                        'type' => 'test-users',
                        'id' => '2',
                    ],
                ]
            ],
        ];

        $datasource = new Datasource();

        try {
            $t = new \CFX\JsonApi\Test\User($datasource, $data);
            $this->fail("Should have thrown an exception");
        } catch (\Error $e) {
            $this->assertContains("undefined method", $e->getMessage());
        }
    }
     */

    public function testSerializesCorrectly() {
        $data = [
            'type' => 'test-users',
            'attributes' => [
                'name' => 'Jim Chavo',
                'dob' => '12345',
            ],
            'relationships' => [
                'friends' => [
                    'data' => [
                        [
                            'type' => 'test-users',
                            'id' => '1',
                        ],
                        [
                            'type' => 'test-users',
                            'id' => '2',
                        ],
                    ],
                ],
                'boss' => [
                    'data' => [
                        'type' => 'test-users',
                        'id' => '3',
                    ],
                ],
            ],
        ];

        $datasource = new Datasource();

        $t = new \CFX\JsonApi\Test\User($datasource, $data);

        $data = [
            'type' => $data['type'],
            'id' => null,
            'attributes' => $data['attributes'],
            'relationships' => $data['relationships'],
        ];

        $this->assertEquals(json_encode($data), json_encode($t), "Should have serialized back to the original input structure");
    }
}

