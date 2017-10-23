<?php
namespace CFX\Persistence\Rest;

class GenericDatasourceTest extends \PHPUnit\Framework\TestCase {
    public function testCanCreateGenericDatasource() {
        $httpClient = new \CFX\Persistence\Test\HttpClient();
        $context = new Test\DataContext('https://null.cfxtrading.com', '12345', 'abcde', $httpClient);
        $people = new GenericDatasource($context, 'test-people', "\\CFX\\Persistence\\Test\\Person");
        $this->assertEquals('test-people', $people->getResourceType());
        $this->assertInstanceOf('\\CFX\\Persistence\\Test\\Person', $people->create());
    }
}

