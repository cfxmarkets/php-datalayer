<?php
namespace CFX\Persistence;

class AbstractDatasourceTest extends \PHPUnit\Framework\TestCase {
    public function testThrowsErrorOnNoResourceTypeSet() {
        $context = new Test\GenericDataContext();
        try {
            $ds = new Test\UnspecifiedDatasource($context);
            $this->fail("Should have thrown an exception");
        } catch (\RuntimeException $e) {
            $this->assertContains("Programmer: You need to define this subclient's `\$resourceType` attribute.", $e->getMessage());
        }
    }

    public function testThrowsUsefulExceptionOnFailureToConvert() {
        $context = new Test\GenericDataContext();
        $ds = new Test\PeopleDatasource($context);
        $person = new Test\Person($ds, ['attributes' => [ 'name' => 'Jim Chavo' ]]);
        try {
            $ds->convert($person, 'private');
            $this->fail("Should have thrown an exception");
        } catch (\RuntimeException $e) {
            $this->assertContains("Programmer: Don't know how to convert resources to type `private`.", $e->getMessage());
        }
    }

    public function testSuccessfullyCreatesResourceCollection() {
        $context = new Test\GenericDataContext();
        $ds = new Test\PeopleDatasource($context);
        $c = $ds->newCollection([], true);
        $this->assertInstanceOf("\\CFX\\JsonApi\\ResourceCollection", $c);
    }

    public function testGetCurrentDataNullsOutData() {
        $context = new Test\GenericDataContext();
        $ds = new Test\PeopleDatasource($context);
        $ds->setCurrentData('jim chavo');
        $this->assertEquals('jim chavo', $ds->getCurrentData());
        $this->assertNull($ds->getCurrentData());
    }

    public function testSaveThrowsExceptionOnErrors() {
        $context = new Test\GenericDataContext();
        $ds = new Test\PeopleDatasource($context);
        $person = $ds->create(['attributes' => ['name' => 'bad']]);
        try {
            $ds->save($person);
            $this->fail("Should have thrown an exception");
        } catch (\CFX\JsonApi\BadInputException $e) {
            $this->assertContains("bad input", strtolower($e->getMessage()));
        }
    }

    public function testSuccessfullyParsesGenericQuery() {
        $context = new Test\GenericDataContext();
        $ds = new Test\PeopleDatasource($context);
        $q = $ds->testDSLParser('id=1test2');

        $this->assertEquals('`id` = ?', $q->getWhere());
        $this->assertEquals('1test2', $q->getParams()[0]);
    }

    public function testSaveDelegatesToSaveNewOnNewObjectsAndSaveExistingForExisting() {
        $context = new Test\GenericDataContext();
        $ds = new Test\PeopleDatasource($context);
        $person = $ds->create(['attributes' => ['name' => 'Jim Chavo']]);

        $ds->save($person);
        $this->assertEquals('new', $ds->getSaveType());

        $person = $ds->get('id=1');
        $person->setName("James R. Chavo, Jr.");
        $ds->save($person);
        $this->assertEquals('existing', $ds->getSaveType());
    }

    public function testInflateDataSuccessfullyInflatesObjects() {
        $context = new Test\GenericDataContext();
        $ds = new Test\PeopleDatasource($context);
        $person = $ds->get('id=1');
        $this->assertInstanceOf("\\CFX\\JsonApi\\ResourceInterface", $person);
    }

    public function testInflateDataSuccessfullyInflatesCollections() {
        $context = new Test\GenericDataContext();
        $ds = new Test\PeopleDatasource($context);
        $people = $ds->get();
        $this->assertInstanceOf("\\CFX\\JsonApi\\ResourceCollectionInterface", $people);
    }

    public function testInflateDataSuccessfullyDelegatesObjectAndCollectionCreation() {
        $context = new Test\GenericDataContext();
        $ds = new Test\PeopleDatasource($context);
        $person = $ds->get('id=1');
        $this->assertInstanceOf("\\CFX\\Persistence\\Test\\Person", $person);

        $people = $ds->get();
        $this->assertInstanceOf("\\CFX\\Persistence\\Test\\PeopleCollection", $people);
    }

    public function testInflateDataYieldsValidResourceOrCollection() {
        $this->markTestIncomplete();
    }

    /**
     * This tests to ensure that if you send the function raw object data ([ 'id' => '1234', ...] instead of [ ['id' => '1234', ...] ])
     * the function throws an exception.
     */
    public function testInflateDataThrowsErrorWhenGivenObjectIsNotTableFormat() {
        $this->markTestIncomplete();
    }
}

