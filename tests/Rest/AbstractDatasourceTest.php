<?php
namespace CFX\Persistence\Test;

class AbstractDatasourceTest extends \PHPUnit\Framework\TestCase {
    protected $httpClient;
    protected $context;
    protected $datasource;

    public function setUp() {
        $this->httpClient = new HttpClient();
        $this->context = new RestDataContext("https://null.cfxtrading.com", '12345', 'abcde', $this->httpClient);
        $this->people = new RestPeopleDatasource($this->context);
    }

    protected function setNextResponse($body = '', $status=200, array $headers=[]) {
        if (is_array($body)) {
            if (!array_key_exists('data', $body) && !array_key_exists('errors', $body)) throw new \RuntimeException("Tester: You must set either `data` or `errors` in the body for your response.");
            $body = json_encode($body);
        }

        $this->httpClient->setNextResponse(
            new \GuzzleHttp\Message\Response(
                $status,
                $headers,
                \GuzzleHttp\Stream\Stream::factory($body)
            )
        );
    }

    public function testUsesResourceTypeAsEndpoint() {
        $this->setNextResponse([ 'data' => [] ]);
        $this->people->get();
        $r = $this->httpClient->getLastRequest();
        $this->assertEquals('https://null.cfxtrading.com/tester/v1.0.0/test-people', (string)$r->getUrl());
    }

    public function testReturnsResourceOrResourceCollection() {
        $this->setNextResponse(['data' => [ Person::getTestData() ]]);
        $people = $this->people->get();
        $this->assertInstanceOf("\\CFX\\JsonApi\\ResourceCollectionInterface", $people);

        $this->setNextResponse(['data' => Person::getTestData() ]);
        $person = $this->people->get('id=1');
        $this->assertInstanceOf("\\CFX\\JsonApi\\ResourceInterface", $person);
    }

    public function testSaveWorksAsExpected() {
        $testData = Person::getTestData();
        $this->setNextResponse(['data' => $testData ]);
        unset($testData['id']);

        $person = $this->people->create($testData);
        $person->save();

        $r = $this->httpClient->getLastRequest();
        $this->assertEquals("POST", $r->getMethod());
        $receivedPerson = $this->people->create(json_decode($r->getBody(), true)['data']);
        $receivedPerson->setId('1');
        $this->assertEquals(json_encode($person), json_encode($receivedPerson));

        $person->setId('1');
        $person->setName('James Chavo');
        $this->setNextResponse(['data' => $person ]);
        $person->save();

        $r = $this->httpClient->getLastRequest();
        $this->assertEquals("PATCH", $r->getMethod());
        $receivedPerson = $this->people->create(json_decode($r->getBody(), true)['data']);
        $this->assertEquals(json_encode($person), json_encode($receivedPerson));
    }

    public function testDeleteReceivesEitherResourceOrId() {
        // String ID
        $this->setNextResponse();
        $this->people->delete('1');
        $r = $this->httpClient->getLastRequest();

        $this->assertEquals('DELETE', $r->getMethod());
        $this->assertEquals('https://null.cfxtrading.com/tester/v1.0.0/test-people/1', (string)$r->getUrl());

        // Resource
        $this->setNextResponse();
        $person = $this->people->create(Person::getTestData());
        $this->people->delete($person);
        $r = $this->httpClient->getLastRequest();

        $this->assertEquals('DELETE', $r->getMethod());
        $this->assertEquals('https://null.cfxtrading.com/tester/v1.0.0/test-people/1', (string)$r->getUrl());
    }

    public function testDelegatesSendRequestThroughOverridableCall() {
        $this->setNextResponse();
        $this->people->delete('1');
        $this->assertTrue($this->people->requestWasDelegated());
    }
}

