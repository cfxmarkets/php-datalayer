<?php
namespace CFX\JsonApi\Test;

use \CFX\JsonApi\Document;
use \CFX\JsonApi\Response;
use \CFX\JsonApi\BadContentTypeException;

class ResponseTest extends \PHPUnit\Framework\TestCase {
    public function testInstantiatesWithoutContentType() {
        $r = new Response(200);
        $this->assertFalse($r->hasHeader('Content-Type'), "Content-Type shouldn't be set with no content");

        $r = new Response(200, [], null);
        $this->assertFalse($r->hasHeader('Content-Type'), "Content-Type shouldn't be set with no content");
    }

    public function testThrowsErrorOnInstantiationWithBody() {
        try {
            $r = new Response(200, [], '{"data":{}}');
            $this->fail("Should have thrown an error");
        } catch (\RuntimeException $e) {
            $this->assertContains("`withJsonApiDoc`", $e->getMessage(), "Should have indicated that `withJsonApiDoc` should be used instead of the body parameter");
        }
    }

    public function testThrowsErrorOnUseOfWithBody() {
        $r = new Response(200);
        try {
            $r->withBody(\GuzzleHttp\Psr7\stream_for(''));
            $this->fail("Should have thrown an error");
        } catch(\RuntimeException $e) {
            $this->assertContains("`withJsonApiDoc`", $e->getMessage(), "Should have indicated that `withJsonApiDoc should be used instead of `withBody`");
        }
    }

    public function testThrowsErrorOnBadContentType() {
        try {
            $r = new Response(200, [ "Content-Type" => "application/json" ]);
            $this->fail("Should have thrown exception");
        } catch (BadContentTypeException $e) {
            $this->assertTrue(true, "This is the expected behavior");
        }

        try {
            $r = new Response(200);
            $r = $r->withHeader('Content-Type', 'application/json');
            $this->fail("Should have thrown an error");
        } catch (BadContentTypeException $e) {
            $this->assertTrue(true, "Should have thrown exception");
        }
    }

    public function testShouldBeAbleToAddCorrectContentType() {
        $r = new Response();
        $r = $r->withHeader('Content-Type', 'application/vnd.api+json');
        $this->assertEquals('application/vnd.api+json', $r->getHeader('Content-Type')[0], "Should have added the correct content type");
    }

    public function testShouldAddContentTypeHeaderOnBodyAdd() {
        $r = new Response(200);
        $this->assertFalse($r->hasHeader('Content-Type'), "Shouldn't have content type yet");

        $r = $r->withJsonApiDoc(new Document(new Factory()));
        $this->assertEquals('application/vnd.api+json; charset=utf-8', $r->getHeaderLine('Content-Type'), "Should have correct Content type");

        $r = $r->withJsonApiDoc(null);
        $this->assertFalse($r->hasHeader('Content-Type'), "Should not have a content type after body removed");
    }
}

