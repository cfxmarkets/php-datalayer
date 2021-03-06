<?php
namespace CFX\Persistence\Test;

class AbstractDataContextTest extends \PHPUnit\Framework\TestCase {
    public function testThrowsExceptionOnNoApiName() {
        $name = RestDataContext::getStaticApiName();
        RestDataContext::setApiName(null);
        try {
            $context = new RestDataContext('https://null.cfxtrading.com', '12345', 'abcde', new HttpClient());
            $this->fail("Should have thrown exception");
        } catch (\RuntimeException $e) {
            $this->assertContains("Programmer: You must define the \$apiName property", $e->getMessage());
        }
        RestDataContext::setApiName($name);
    }

    public function testThrowsExceptionOnNoApiVersion() {
        $version = RestDataContext::getStaticApiVersion();
        RestDataContext::setApiVersion(null);
        try {
            $context = new RestDataContext('https://null.cfxtrading.com', '12345', 'abcde', new HttpClient());
            $this->fail("Should have thrown exception");
        } catch (\RuntimeException $e) {
            $this->assertContains("Programmer: You must define the \$apiVersion property", $e->getMessage());
        }
        RestDataContext::setApiVersion($version);
    }

    public function testInstantiatesCorrectly() {
        $context = new RestDataContext('https://null.cfxtrading.com', '12345', 'abcde', new HttpClient());
        $this->assertEquals('https://null.cfxtrading.com', $context->getBaseUri());
        $this->assertEquals('12345', $context->getApiKey());
        $this->assertEquals('abcde', $context->getApiKeySecret());
        $this->assertInstanceOf("\\CFX\\Persistence\\Test\\HttpClient", $context->getHttpClient());
        $this->assertEquals('tester', $context->getApiName());
        $this->assertEquals('1.0.0', $context->getApiVersion());
    }

    public function testComposesUriCorrectly() {
        $context = new RestDataContext('https://null.cfxtrading.com', '12345', 'abcde', new HttpClient());
        $this->assertEquals("https://null.cfxtrading.com/tester/v1.0.0/tests", $context->getComposedUri("/tests"));
    }

    public function testSendRequestReturnsResponse() {
        $httpClient = new HttpClient();
        $cfx = new RestDataContext('https://null.cfxtrading.com', '12345', 'abcde', $httpClient);
        $httpClient->setNextResponse(new \GuzzleHttp\Psr7\Response(200));
        $r = $cfx->sendRequest('GET', '/tests');
        $this->assertInstanceOf("\\Psr\\Http\\Message\\ResponseInterface", $r);
    }

    public function testThrowsExceptionsOnNon200Responses() {
        $httpClient = new HttpClient();
        $cfx = new RestDataContext('https://null.cfxtrading.com', '12345', 'abcde', $httpClient);

        $httpClient->setNextResponse(new \GuzzleHttp\Psr7\Response(599));
        try {
            $cfx->sendRequest('GET', '/assets');
            $this->fail("Should have thrown exception");
        } catch (\RuntimeException $e) {
            $this->assertContains('server error', strtolower($e->getMessage()));
        }

        $httpClient->setNextResponse(new \GuzzleHttp\Psr7\Response(500));
        try {
            $cfx->sendRequest('GET', '/assets');
            $this->fail("Should have thrown exception");
        } catch (\RuntimeException $e) {
            $this->assertContains('server error', strtolower($e->getMessage()));
        }

        $httpClient->setNextResponse(new \GuzzleHttp\Psr7\Response(499));
        try {
            $cfx->sendRequest('GET', '/assets');
            $this->fail("Should have thrown exception");
        } catch (\RuntimeException $e) {
            $this->assertContains('user error', strtolower($e->getMessage()));
        }

        $httpClient->setNextResponse(new \GuzzleHttp\Psr7\Response(400));
        try {
            $cfx->sendRequest('GET', '/assets');
            $this->fail("Should have thrown exception");
        } catch (\RuntimeException $e) {
            $this->assertContains('user error', strtolower($e->getMessage()));
        }

        $httpClient->setNextResponse(new \GuzzleHttp\Psr7\Response(399));
        try {
            $cfx->sendRequest('GET', '/assets');
            $this->fail("Should have thrown exception");
        } catch (\RuntimeException $e) {
            $this->assertContains('3xx', strtolower($e->getMessage()));
        }

        $httpClient->setNextResponse(new \GuzzleHttp\Psr7\Response(300));
        try {
            $cfx->sendRequest('GET', '/assets');
            $this->fail("Should have thrown exception");
        } catch (\RuntimeException $e) {
            $this->assertContains('3xx', strtolower($e->getMessage()));
        }

        $httpClient->setNextResponse(new \GuzzleHttp\Psr7\Response(199));
        try {
            $cfx->sendRequest('GET', '/assets');
            $this->fail("Should have thrown exception");
        } catch (\RuntimeException $e) {
            $this->assertContains('1xx', strtolower($e->getMessage()));
        }

        $httpClient->setNextResponse(new \GuzzleHttp\Psr7\Response(100));
        try {
            $cfx->sendRequest('GET', '/assets');
            $this->fail("Should have thrown exception");
        } catch (\RuntimeException $e) {
            $this->assertContains('1xx', strtolower($e->getMessage()));
        }

        $httpClient->setNextResponse(new \GuzzleHttp\Psr7\Response(299));
        $r = $cfx->sendRequest('GET', '/assets');
        $this->assertEquals(299, $r->getStatusCode());

        $httpClient->setNextResponse(new \GuzzleHttp\Psr7\Response(200));
        $r = $cfx->sendRequest('GET', '/assets');
        $this->assertEquals(200, $r->getStatusCode());
    }

    public function testDebugLogsWorkCorrectly()
    {
        $httpClient = new HttpClient();
        $cfx = new RestDataContext('https://null.cfxtrading.com', '12345', 'abcde', $httpClient);

        try {
            $cfx->debugGetRequestLog();
            $this->fail("Should have thrown exception");
        } catch (\RuntimeException $e) {
            $this->assertContains("enable debugging", $e->getMessage());
        }

        try {
            $cfx->debugGetResponseLog();
            $this->fail("Should have thrown exception");
        } catch (\RuntimeException $e) {
            $this->assertContains("enable debugging", $e->getMessage());
        }

        $cfx->setDebug(true);

        $httpClient->setNextResponse(new \GuzzleHttp\Psr7\Response(200));
        $cfx->sendRequest('GET', '/assets');
        $this->assertTrue(is_array($cfx->debugGetRequestLog()), "Should have returned an array with requests in it");
        $this->assertTrue(is_array($cfx->debugGetResponseLog()), "Should have returned an array with responses in it");
        $this->assertEquals(1, count($cfx->debugGetRequestLog()));
        $this->assertEquals(1, count($cfx->debugGetResponseLog()));
        $this->assertInstanceOf("\\Psr\\Http\\Message\\RequestInterface", $cfx->debugGetRequestLog()[0]);
        $this->assertInstanceOf("\\Psr\\Http\\Message\\ResponseInterface", $cfx->debugGetResponseLog()[0]);

        $cfx->debugClearRequestLog();
        $cfx->debugClearResponseLog();
        $this->assertEquals(0, count($cfx->debugGetRequestLog()));
        $this->assertEquals(0, count($cfx->debugGetResponseLog()));
    }

    public function testDuplicateResourceException()
    {
        $httpClient = new HttpClient();
        $cfx = new RestDataContext('https://null.cfxtrading.com', '12345', 'abcde', $httpClient);

        $httpClient->setNextResponse(new \GuzzleHttp\Psr7\Response(
            409,
            [],
            \GuzzleHttp\Psr7\stream_for(json_encode([
                'errors' => [
                    [
                        'status' => 409,
                        'title' => "Duplicate Resource",
                        'detail' => "You have tried to create a duplicate resource",
                        "meta" => [
                            "duplicateResource" => [
                                'type' => 'test-people',
                                'id' => '12345abcde',
                            ],
                        ],
                    ],
                ],
            ])))
        );

        try {
            $user = $cfx->testPeople->create()
                ->setName("Test Testerson")
                ->save();

            $this->fail("Should have thrown exception");
        } catch (\CFX\Persistence\DuplicateResourceException $e) {
            $user = $e->getDuplicateResource();
            $this->assertInstanceOf("\\CFX\\Persistence\\Test\\Person", $user);
            $this->assertEquals("test-people", $user->getResourceType());
            $this->assertEquals("12345abcde", $user->getId());
        }
    }

    public function testHandlesGuzzle53RequestOptions()
    {
        $httpClient = new HttpClient();
        $cfx = new RestDataContext('https://null.cfxtrading.com', '12345', 'abcde', $httpClient);

        // Test headers
        $httpClient->setNextResponse(new \GuzzleHttp\Psr7\Response(200));
        $cfx->sendRequest("GET", "/some-resource", [
            'headers' => [
                'X-Test1' => 'test1',
                'X-Test2' => [
                    'Test2a',
                    'Test2b',
                ]
            ]
        ]);
        $r = $httpClient->getLastRequest();
        $this->assertEquals("test1", $r->getHeaderLine('X-Test1'));
        $this->assertEquals("Test2a, Test2b", $r->getHeaderLine("X-Test2"));

        // Test string body
        $httpClient->setNextResponse(new \GuzzleHttp\Psr7\Response(200));
        $cfx->sendRequest("GET", "/some-resource", ['body' => 'someparam=1&otherparam=2']);
        $r = $httpClient->getLastRequest();
        $this->assertEquals('someparam=1&otherparam=2', (string)$r->getBody());

        // Test array body
        $httpClient->setNextResponse(new \GuzzleHttp\Psr7\Response(200));
        $cfx->sendRequest("GET", "/some-resource", ['body' => [ 'someparam' => 1, 'otherparam' => 2]]);
        $r = $httpClient->getLastRequest();
        $this->assertEquals('someparam=1&otherparam=2', (string)$r->getBody());

        // Test stream body
        $httpClient->setNextResponse(new \GuzzleHttp\Psr7\Response(200));
        $cfx->sendRequest("GET", "/some-resource", ['body' => \GuzzleHttp\Psr7\stream_for('someparam=1&otherparam=2')]);
        $r = $httpClient->getLastRequest();
        $this->assertEquals('someparam=1&otherparam=2', (string)$r->getBody());

        // Test json
        $httpClient->setNextResponse(new \GuzzleHttp\Psr7\Response(200));
        $cfx->sendRequest("GET", "/some-resource", ['json' => [ 'someparam' => 1, 'otherparam' => 2]]);
        $r = $httpClient->getLastRequest();
        $this->assertEquals('{"someparam":1,"otherparam":2}', (string)$r->getBody());

        // Test query
        $httpClient->setNextResponse(new \GuzzleHttp\Psr7\Response(200));
        $cfx->sendRequest("GET", "/some-resource", ['query' => [ 'someparam' => 1, 'otherparam' => 2]]);
        $r = $httpClient->getLastRequest();
        $this->assertEquals('someparam=1&otherparam=2', $r->getUri()->getQuery());

        // Test additional query
        $httpClient->setNextResponse(new \GuzzleHttp\Psr7\Response(200));
        $cfx->sendRequest("GET", "/some-resource?firstparam=0", ['query' => [ 'someparam' => 1, 'otherparam' => 2]]);
        $r = $httpClient->getLastRequest();
        $this->assertEquals('firstparam=0&someparam=1&otherparam=2', $r->getUri()->getQuery());

        // Test string query
        $httpClient->setNextResponse(new \GuzzleHttp\Psr7\Response(200));
        $cfx->sendRequest("GET", "/some-resource?firstparam=0", ['query' => 'someparam=1&otherparam=2']);
        $r = $httpClient->getLastRequest();
        $this->assertEquals('firstparam=0&someparam=1&otherparam=2', $r->getUri()->getQuery());
    }
}

