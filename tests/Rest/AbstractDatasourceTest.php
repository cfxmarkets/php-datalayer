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
            new \GuzzleHttp\Psr7\Response(
                $status,
                $headers,
                \GuzzleHttp\Psr7\stream_for($body)
            )
        );
    }

    public function testUsesResourceTypeAsEndpoint() {
        $this->setNextResponse([ 'data' => [] ]);
        $this->people->get();
        $r = $this->httpClient->getLastRequest();
        $this->assertEquals('https://null.cfxtrading.com/tester/v1.0.0/test-people', (string)$r->getUri());
    }

    public function testReturnsResourceOrResourceCollection() {
        $this->setNextResponse(['data' => [ Person::getTestData() ]]);
        $people = $this->people->get();
        $this->assertInstanceOf("\\CFX\\JsonApi\\ResourceCollectionInterface", $people);
        $this->assertInstanceOf("\\CFX\\Persistence\\Test\\Person", $people[0]);

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

        $expected = json_decode(json_encode($person), true);
        $actual = json_decode($r->getBody(), true)['data'];
        $test = function($expected, $actual) use (&$test) {
            if (is_array($expected)) {
                $this->assertTrue(is_array($actual), "Expected array, but got a value instead");
                $this->assertEquals(array_keys($expected), array_keys($actual), "Keys should be the same", 0, 20, true);
                foreach($expected as $key => $val) {
                    $test($val, $actual[$key]);
                }
            } else {
                $this->assertEquals($expected, $actual);
            }
        };
        $test($expected, $actual);
    }

    public function testDeleteReceivesEitherResourceOrId() {
        // String ID
        $this->setNextResponse();
        $this->people->delete('1');
        $r = $this->httpClient->getLastRequest();

        $this->assertEquals('DELETE', $r->getMethod());
        $this->assertEquals('https://null.cfxtrading.com/tester/v1.0.0/test-people/1', (string)$r->getUri());

        // Resource
        $data = Person::getTestData();
        $id = $data['id'];
        unset($data['id']);
        $person = $this->people->create($data)->setId($id);

        $this->setNextResponse();
        $this->people->delete($person);
        $r = $this->httpClient->getLastRequest();

        $this->assertEquals('DELETE', $r->getMethod());
        $this->assertEquals('https://null.cfxtrading.com/tester/v1.0.0/test-people/1', (string)$r->getUri());
    }

    public function testDelegatesSendRequestThroughOverridableCall() {
        $this->setNextResponse();
        $this->people->delete('1');
        $this->assertTrue($this->people->requestWasDelegated());
    }
}

