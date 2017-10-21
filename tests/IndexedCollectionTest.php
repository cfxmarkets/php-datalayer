<?php

use \CFX\JsonApi\Test\IndexedCollection;

class IndexedCollectionTest extends \PHPUnit\Framework\TestCase {
    public function testCanCreateEmptyCollection() {
        $t = new IndexedCollection();
        $this->assertTrue($t instanceof \CFX\JsonApi\IndexedCollectionInterface, "Should have created an indexed collection");
    }

    public function testThrowsErrorOnNonNamedMemeber() {
        $t = new IndexedCollection();
        try {
            $t['test'] = (object)[];
            $this->fail('Should have thrown an exception adding non-named member');
        } catch (\InvalidArgumentException $e) {
            $this->assertTrue(true, "This is the expected behavior");
        }
    }

    public function testAcceptsNamedMember() {
        $t = new IndexedCollection();
        $t['test'] = new \CFX\JsonApi\Test\NamedMember();
        $this->assertTrue($t['test'] instanceof \CFX\JsonApi\NamedMemberInterface, "Should hold an instance of NamedMemberInterface");
    }

    public function testSetsMemberNameToStringOffsetName() {
        $t = new IndexedCollection();
        $m = new \CFX\JsonApi\Test\NamedMember();
        $this->assertNull($m->getMemberName(), 'Member name should default to null.');
        $t['test'] = $m;
        $this->assertEquals('test', $m->getMemberName(), "Member name should have changed to 'test'.");

        $m = new \CFX\JsonApi\Test\NamedMember();
        $this->assertNull($m->getMemberName(), 'Member name should default to null.');
        $t[] = $m;
        $this->assertNull($m->getMemberName(), "Member name should still be null.");
    }

    public function testThrowsErrorOnSerializeWithUnnamedMembers() {
        $t = new IndexedCollection();
        $t[] = new \CFX\JsonApi\Test\NamedMember();
        try {
            json_encode($t);
            $this->fail("Should have thrown an error");
        } catch (\Exception $e) {
            if (!($e->getPrevious() instanceof \CFX\JsonApi\UnserializableObjectStateException)) throw $e;
            $this->assertTrue(true, "This is the desired behavior");
        }
    }

    public function testThrowsErrorOnAddMembersWithDuplicateNames() {
        $t = new IndexedCollection();
        $t['test'] = new \CFX\JsonApi\Test\NamedMember();
        $t['test']->setMemberName('test');

        try {
            $t[] = $t['test'];
            $this->fail("Should have thrown an exception");
        } catch (\CFX\JsonApi\CollectionConflictingMemberException $e) {
            $this->assertTrue(true, "This is the expected behavior");
        }
    }

    public function testThrowsErrorOnAddMemberWithMismatchingName() {
        $t = new IndexedCollection();
        try {
            $m = new \CFX\JsonApi\Test\NamedMember();
            $m->setMemberName('test');
            $t['test2'] = $m;
            $this->fail("Should have thrown an exception");
        } catch (\InvalidArgumentException $e) {
            $this->assertContains("index that doesn't match its name", $e->getMessage(), "Should indicate that the index doesn't match the given name");
        }

        /*
         * Not implementable
        $this->assertTrue(array_key_exists('test', $t), "`test` key should exist.");
        $this->assertFalse(array_key_exists('test2', $t), "`test2` key shouldn't exist yet.");
        $t[] = $m;
        $t['test']->setMemberName('test2');
        $this->assertTrue(array_key_exists('test2', $t), "`test2` key should now exist.");
        $this->assertFalse(array_key_exists('test', $t), "`test` key should no longer exist.");
         */
    }

    public function testThrowsErrorOnSerializeMembersWithDuplicateNames() {
        $t = new IndexedCollection();
        $t['test'] = new \CFX\JsonApi\Test\NamedMember();
        $t['test']->setMemberName('test');
        $t['test2'] = new \CFX\JsonApi\Test\NamedMember();
        $t['test2']->setMemberName('test');

        try {
            json_encode($t);
            $this->fail("Should have thrown an error");
        } catch (\Exception $e) {
            if (!($e->getPrevious() instanceof \CFX\JsonApi\CollectionConflictingMemberException)) throw $e;
            $this->assertTrue(true, "This is the expected behavior");
        }
    }

    public function testSuccessfullyHandlesValidCollections() {
        $t = new IndexedCollection();
        $t['test'] = new \CFX\JsonApi\Test\NamedMember();
        $t['test']->setMemberName('test');
        $t['test']->setData('testData');
        $t['test2'] = new \CFX\JsonApi\Test\NamedMember();
        $t['test2']->setMemberName('test-two');
        $t['test2']->setData('testData2');

        $this->assertEquals('{"test":"testData","test-two":"testData2"}', json_encode($t), "Should have serialized correctly");
    }

    public function testActsLikeAnArray() {
        $t = new IndexedCollection();
        $t['test'] = new \CFX\JsonApi\Test\NamedMember();
        $t['test']->setMemberName('test');
        $t['test']->setData('testData');
        $t['test2'] = new \CFX\JsonApi\Test\NamedMember();
        $t['test2']->setMemberName('test-two');
        $t['test2']->setData('testData2');

        $this->assertEquals(2, count($t), "Should have counted correctly");

        $test = [];
        foreach($t as $k => $v) $test[$v->getMemberName()] = $v->getData();
        $this->assertEquals('{"test":"testData","test-two":"testData2"}', json_encode($test), "Should have transfered correctly to a flat array");
    }
}

