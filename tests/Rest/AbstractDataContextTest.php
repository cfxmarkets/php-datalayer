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
        $httpClient->setNextResponse(new \GuzzleHttp\Message\Response(200));
        $r = $cfx->sendRequest('GET', '/tests');
        $this->assertInstanceOf("\\GuzzleHttp\\Message\\Response", $r);
    }

    public function testThrowsExceptionsOnNon200Responses() {
        $httpClient = new HttpClient();
        $cfx = new RestDataContext('https://null.cfxtrading.com', '12345', 'abcde', $httpClient);

        $httpClient->setNextResponse(new \GuzzleHttp\Message\Response(599));
        try {
            $cfx->sendRequest('GET', '/assets');
            $this->fail("Should have thrown exception");
        } catch (\RuntimeException $e) {
            $this->assertContains('server error', strtolower($e->getMessage()));
        }

        $httpClient->setNextResponse(new \GuzzleHttp\Message\Response(500));
        try {
            $cfx->sendRequest('GET', '/assets');
            $this->fail("Should have thrown exception");
        } catch (\RuntimeException $e) {
            $this->assertContains('server error', strtolower($e->getMessage()));
        }

        $httpClient->setNextResponse(new \GuzzleHttp\Message\Response(499));
        try {
            $cfx->sendRequest('GET', '/assets');
            $this->fail("Should have thrown exception");
        } catch (\RuntimeException $e) {
            $this->assertContains('user error', strtolower($e->getMessage()));
        }

        $httpClient->setNextResponse(new \GuzzleHttp\Message\Response(400));
        try {
            $cfx->sendRequest('GET', '/assets');
            $this->fail("Should have thrown exception");
        } catch (\RuntimeException $e) {
            $this->assertContains('user error', strtolower($e->getMessage()));
        }

        $httpClient->setNextResponse(new \GuzzleHttp\Message\Response(399));
        try {
            $cfx->sendRequest('GET', '/assets');
            $this->fail("Should have thrown exception");
        } catch (\RuntimeException $e) {
            $this->assertContains('3xx', strtolower($e->getMessage()));
        }

        $httpClient->setNextResponse(new \GuzzleHttp\Message\Response(300));
        try {
            $cfx->sendRequest('GET', '/assets');
            $this->fail("Should have thrown exception");
        } catch (\RuntimeException $e) {
            $this->assertContains('3xx', strtolower($e->getMessage()));
        }

        $httpClient->setNextResponse(new \GuzzleHttp\Message\Response(199));
        try {
            $cfx->sendRequest('GET', '/assets');
            $this->fail("Should have thrown exception");
        } catch (\RuntimeException $e) {
            $this->assertContains('1xx', strtolower($e->getMessage()));
        }

        $httpClient->setNextResponse(new \GuzzleHttp\Message\Response(100));
        try {
            $cfx->sendRequest('GET', '/assets');
            $this->fail("Should have thrown exception");
        } catch (\RuntimeException $e) {
            $this->assertContains('1xx', strtolower($e->getMessage()));
        }

        $httpClient->setNextResponse(new \GuzzleHttp\Message\Response(299));
        $r = $cfx->sendRequest('GET', '/assets');
        $this->assertEquals(299, $r->getStatusCode());

        $httpClient->setNextResponse(new \GuzzleHttp\Message\Response(200));
        $r = $cfx->sendRequest('GET', '/assets');
        $this->assertEquals(200, $r->getStatusCode());
    }
}

