<?php
namespace CFX\Persistence;

class AbstractDataContextTest extends \PHPUnit\Framework\TestCase {
    public function testConvertsPropertyCallsToDatasourceCalls() {
        $context = new Test\GenericDataContext();
        $testPeople = $context->testPeople;
        $this->assertInstanceOf("\\CFX\\Persistence\\DatasourceInterface", $testPeople);
    }

    public function testCanGetDatasourcesByJsonApiType() {
        $context = new Test\GenericDataContext();

        $testPeople = $context->datasourceForType('people');
        $this->assertInstanceOf("\\CFX\\Persistence\\DatasourceInterface", $testPeople);

        $testTestPeople = $context->datasourceForType('test-test-people');
        $this->assertInstanceOf("\\CFX\\Persistence\\DatasourceInterface", $testTestPeople);
    }

    public function testThrowsErrorOnUnrecognizedResourceCreation() {
        try {
            $context = new Test\GenericDataContext();
            $person = $context->newResource(null, 'not-valids');
            $this->fail("Should have thrown an exception");
        } catch (UnknownResourceTypeException $e) {
            $this->assertContains("Type `not-valids` is unknown", $e->getMessage());
        }
    }

    public function testCreatesNewResourceFromJsonApiType() {
        $context = new Test\GenericDataContext();
        $person = $context->newResource(null, 'test-people');
        $this->assertInstanceOf("\\CFX\\JsonApi\\ResourceInterface", $person);
    }

    public function testThrowsErrorOnUnrecognizedResourceConversion() {
        try {
            $context = new Test\GenericDataContext();
            $this->markTestIncomplete("Need to instantiate a resource that's not natively handled by this context and try to convert it.");
            $nonNative = new Test\NonNativePerson(new Test\NonNativeDatasource($context));
            $fantasticPerson = $context->convertResource($nonNative, 'fantastic');
            $this->fail("Should have thrown an exception");
        } catch (UnknownResourceTypeException $e) {
            $this->assertContains("Type `not-native` is unknown", $e->getMessage());
        }
    }

    public function testSuccessfullyTriesToConvertResource() {
        try {
            $context = new Test\GenericDataContext();
            $person = $context->testPeople->create();
            $context->convertResource($person, 'fantastic');
            $this->fail("Should have thrown an exception");
        } catch (\RuntimeException $e) {
            $this->assertContains("Programmer: Don't know how to convert resources to type `fantastic`.", $e->getMessage());
        }
    }

    public function testLazyLoadsDataSources() {
        $context = new Test\GenericDataContext();
        $this->assertFalse(array_key_exists('testPeople', $context->getDatasources()), "Should not have testPeople preloaded in the datasources list");
        $testPeople = $context->testPeople;
        $this->assertInstanceOf("\\CFX\\Persistence\\DatasourceInterface", $testPeople);
        $this->assertTrue(array_key_exists('testPeople', $context->getDatasources()), "TestPeople should now be in list");
        $this->assertInstanceOf("\\CFX\\Persistence\\DatasourceInterface", $context->getDatasources()['testPeople']);
    }
}

