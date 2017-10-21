<?php
namespace CFX\Persistence;

class AbstractDataContextTest extends \PHPUnit\Framework\TestCase {
    public function testLazyLoadsDataSources() {
        $context = new Test\GenericDataContext();
        $this->assertFalse(array_key_exists('tester', $context->getDatasources()), "Should not have tester preloaded in the datasources list");
        $tester = $context->tester;
        $this->assertInstanceOf("\\CFX\\Persistence\\DatasourceInterface", $tester);
        $this->assertTrue(array_key_exists('tester', $context->getDatasources()), "Tester should now be in list");
        $this->assertInstanceOf("\\CFX\\Persistence\\DatasourceInterface", $context->getDatasources()['tester']);
    }
}

