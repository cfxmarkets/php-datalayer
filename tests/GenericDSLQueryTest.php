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
                $this->assertContains("Unacceptable fields, operators, or values found.", $e->getMessage());
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

    public function testDeterminesCollectionCorrectly() {
        $q = GenericDSLQuery::parse(null);
        $this->assertTrue($q->requestingCollection());

        $q = GenericDSLQuery::parse("id=12345");
        $this->assertFalse($q->requestingCollection());
    }

    public function testCanExtendToParseMoreFields() {
        $q = Test\TestDSLQuery::parse("id = 12345 and test1 = 553jjjsd and test2 = someBigId123456");
        $this->assertEquals(12345, $q->getId());
        $this->assertEquals('553jjjsd', $q->getTest1());
        $this->assertEquals('someBigId123456', $q->getTest2());
        $this->assertEquals("`id` = ? and `test1` = ? and `test2` = ?", $q->getWhere());
        $this->assertEquals(['12345', '553jjjsd', 'someBigId123456'], $q->getParams());
        $this->assertEquals("id = 12345 and test1 = 553jjjsd and test2 = someBigId123456", (string)$q);
    }

    public function testCorrectlyComposesWhereWithSet()
    {
        $q = Test\TestDSLQuery::parse("test3 in ('one', 'two', 'three', 'four')");
        $this->assertEquals("`test3` in (?, ?, ?, ?)", $q->getWhere());
        $this->assertEquals([ "one", "two", "three", "four" ], $q->getParams());

        $q = Test\TestDSLQuery::parse("test3 in (five,six,seven)");
        $this->assertEquals("`test3` in (?, ?, ?)", $q->getWhere());
        $this->assertEquals([ "five", "six", "seven" ], $q->getParams());
    }

    public function testRejectsMultipleOperators()
    {
        try {
            $q = Test\TestDSLQuery::parse("id = 12345 and test1 = gsdfff or test2 = asldkfslkdf");
            $this->fail("Should have thrown an exception");
        } catch (\CFX\Persistence\BadQueryException $e) {
            $this->assertContains("Sorry, you can only set one type of logical operator per query for now.", $e->getMessage());
        }
    }

    public function testAllowsOrQueries()
    {
        $q = Test\TestDSLQuery::parse("id = 12345 or test1 = fsdfsd or test2 != sdfsdfs");
        $this->assertTrue($q->includes("id"));
        $this->assertTrue($q->includes("test1"));
        $this->assertFalse($q->includes("test2"));
    }

    public function testOrQueriesDisabledByDefault()
    {
        try {
            $q = GenericDSLQuery::parse("id = 12345 or id = 54321");
            $this->fail("Should have thrown an exception");
        } catch (\CFX\Persistence\BadQueryException $e) {
            $this->assertContains("Unacceptable fields, operators, or values found.", $e->getMessage());
        }
    }

    public function testCanCreateComplexQueries()
    {
        // Create new class with extended facilities
        $query = new class extends GenericDSLQuery {
            protected static function getAcceptableFields()
            {
                return array_merge(
                    parent::getAcceptableFields(),
                    [ "cryptoWallets" ]
                );
            }

            protected static function getComparisonOperators() {
                return array_merge(parent::getComparisonOperators(), [ "includes" ]);
            }

            // Allow fields to be quoted and in parentheses
            protected static function getFieldValueSpecification()
            {
                return "(\(?['\"]?.+?['\"]?(?:, *)?\)?)";
            }

            public function setCryptoWallets($operator, $val)
            {
                if ($operator !== "includes") {
                    throw new \CFX\Persistence\BadQueryException(
                        "You may only use the 'includes' operator when querying crypto wallets"
                    );
                }

                if (!is_array($val)) {
                    $val = array_map(
                        function($v) { return trim($v, "'\""); },
                        preg_split(
                            "/, */",
                            trim($val, " ()")
                        )
                    );
                }

                // strip 0x from beginning, if necessary
                $trimmed = array_map(function($v) { return preg_replace("/^0x/", "", $v); }, $val);

                $placeholders = array_map(function($v) { return "?"; }, $val);

                return $this->setExpressionValue('cryptoWallets', [
                    "string" => "cryptoWallets $operator (".implode(", ", $val).")",
                    "raw" => "EXISTS (".
                        "SELECT * FROM `_crypto-wallets` `w` ".
                        "WHERE ".
                        "`w`.`legalEntityId` = `accts`.`AccountKey` && ".
                        "`id` IN (UNHEX(".implode("),UNHEX(", $placeholders)."))".
                    ")",
                    'value' => $trimmed,
                ]);
            }
            public function getCryptoWallets()
            {
                return $this->getExpressionValue("cryptoWallets");
            }
            public function unsetCryptoWallets()
            {
                return $this->setExpressionValue("cryptoWallets", null);
            }
        };

        $testQuery = "id = 12345 and cryptoWallets includes (0xabc, 0x123, 0x456)";
        $q = $query::parse($testQuery);

        $this->assertEquals($testQuery, $q->__toString());
        $this->assertEquals(
            "`id` = ? and EXISTS (".
                "SELECT * FROM `_crypto-wallets` `w` ".
                "WHERE ".
                "`w`.`legalEntityId` = `accts`.`AccountKey` && ".
                "`id` IN (UNHEX(?),UNHEX(?),UNHEX(?))".
            ")",
            $q->getWhere()
        );
        $this->assertEquals(["12345", "abc", "123", "456"], $q->getParams());
    }
}

