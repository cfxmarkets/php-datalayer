<?php
namespace CFX\Persistence;

class GenericDSLQueryTest extends \PHPUnit\Framework\TestCase {
    public function testCantInstantiateFromConstructor() {
        $this->markTestIncomplete("Can't do this from PHP5.4");
        $q = new GenericDSLQuery();
        $this->assertInstanceOf("\\CFX\\Persistence\\DSLQueryInterface", $q);
    }

    public function testCanInstantiate() {
        $q = GenericDSLQuery::parse('');
        $this->assertInstanceOf("\\CFX\\Persistence\\DSLQueryInterface", $q);
    }

    public function testReturnsEmptyQueryOnNullValue() {
        foreach(['', null] as $str) {
            $q = GenericDSLQuery::parse($str);
            $this->assertInstanceOf("\\CFX\\Persistence\\DSLQueryInterface", $q);
            $this->assertNull($q->getId());
            $this->assertNull($q->getWhere());
            $this->assertEquals([], $q->getParams());
        }
    }

    public function testThrowsExceptionOnUnrecognizedParameters() {
        foreach(['id=12345 and email=test@test.com'] as $dsl) {
            try {
                $q = GenericDSLQuery::parse($dsl);
                $this->fail("Should have thrown exception");
            } catch (\CFX\Persistence\BadQueryException $e) {
                $this->assertContains("Unacceptable fields or values", $e->getMessage());
            }
        }
    }

    public function testParsesValidStringCorrectly() {
        foreach(['1','12345','abasdlasdlg323oij2938fae23f230293'] as $id) {
            $q = GenericDSLQuery::parse("id=$id");
            $this->assertInstanceOf("\\CFX\\Persistence\\DSLQueryInterface", $q);
            $this->assertEquals($id, $q->getId());
            $this->assertEquals("`id` = ?", $q->getWhere());
            $this->assertEquals([$id], $q->getParams());
        }
    }

    public function testCanExtendToParseMoreFields() {
        $q = Test\TestDSLQuery::parse("id = 12345 and test1 = 553jjjsd and test2 = someBigId123456");
        $this->assertEquals(12345, $q->getId());
        $this->assertEquals('553jjjsd', $q->getTest1());
        $this->assertEquals('someBigId123456', $q->getTest2());
        $this->assertEquals("`id` = ? and `test1` = ? and `test2` = ?", $q->getWhere());
        $this->assertEquals(['12345', '553jjjsd', 'someBigId123456'], $q->getParams());
        $this->assertEquals("id=12345 and test1=553jjjsd and test2=someBigId123456", (string)$q);
    }
}

