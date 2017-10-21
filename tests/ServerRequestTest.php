<?php

use CFX\JsonApi\ServerRequest;
use CFX\JsonApi\ProtocolException;
use CFX\JsonApi\BadAcceptException;
use CFX\JsonApi\BadContentTypeException;
use CFX\JsonApi\BadUriException;

class ServerRequestTest extends \PHPUnit\Framework\TestCase {
    public function testThrowsProtocolErrorOnBadAcceptHeader() {
        try {
            $r = new ServerRequest("GET", "https://api.kaelshipman.me/test-resources");
            $r->validateProtocol();
            $this->fail("Should have thrown exception");
        }  catch (BadAcceptException $e) {
            $this->assertTrue(true, "this is the expected behavior");
        }

        try {
            $r = new ServerRequest("GET", "https://api.kaelshipman.me/test-resources", [ 'Accept' => 'application/json' ]);
            $r->validateProtocol();
            $this->fail("Should have thrown exception");
        }  catch (BadAcceptException $e) {
            $this->assertTrue(true, "this is the expected behavior");
        }
    }

    public function testThrowsProtocolErrorOnBadContentTypeHeader() {
        try {
            $r = new ServerRequest("POST", "https://api.kaelshipman.me/test-resources", [ 'Accept' => 'application/vnd.api+json' ], json_encode([ 'data' => [ 'type' => 'test-resources', 'id' => '12345' ] ]));
            $r->validateProtocol();
            $this->fail("Should have thrown exception");
        }  catch (BadContentTypeException $e) {
            $this->assertTrue(true, "this is the expected behavior");
        }

        try {
            $r = new ServerRequest("POST", "https://api.kaelshipman.me/test-resources", [ 'Content-Type' => 'application/json', 'Accept' => 'application/vnd.api+json' ], json_encode([ 'data' => [ 'type' => 'test-resources', 'id' => '12345' ] ]));
            $r->validateProtocol();
            $this->fail("Should have thrown exception");
        }  catch (BadContentTypeException $e) {
            $this->assertTrue(true, "this is the expected behavior");
        }
    }

    public function testThrowsProtocolErrorOnNoResourceDefined() {
        try {
            $r = new ServerRequest("GET", "https://api.kaelshipman.me", [ 'Accept' => 'application/vnd.api+json' ]);
            $r->validateProtocol();
            $this->fail("Should have thrown exception");
        }  catch (BadUriException $e) {
            $this->assertTrue(true, "this is the expected behavior");
        }
    }

    public function testThrowsProtocolErrorOnRelationshipsMisspelled() {
        try {
            $r = new ServerRequest("GET", "https://api.kaelshipman.me/test-resources/12345/reltasdfe/friends", [ 'Accept' => 'application/vnd.api+json' ]);
            $r->validateProtocol();
            $this->fail("Should have thrown exception");
        }  catch (BadUriException $e) {
            $this->assertTrue(true, "this is the expected behavior");
        }
    }

    public function testThrowsProtocolErrorOnExtraParams() {
        try {
            $r = new ServerRequest("GET", "https://api.kaelshipman.me/test-resources/12345/relationships/friends/54321/invalid-extra-param", [ 'Accept' => 'application/vnd.api+json' ]);
            $r->validateProtocol();
            $this->fail("Should have thrown exception");
        }  catch (ProtocolException $e) {
            $this->assertTrue(true, "this is the expected behavior");
        }
    }

    public function testAcceptsGoodGETRequest() {
        $r = new ServerRequest("GET", "https://api.kaelshipman.me/test-resources/12345", [ 'Accept' => 'application/vnd.api+json' ]);
        $this->assertInstanceOf('\\CFX\\JsonApi\\ServerRequest', $r, "Should be a JsonApi ServerRequest object");
    }

    public function testAcceptsGoodPOSTRequest() {
        $r = new ServerRequest("POST", "https://api.kaelshipman.me/test-resources", [ 'Content-Type' => 'application/vnd.api+json', 'Accept' => 'application/vnd.api+json' ], json_encode([ 'data' => [ 'type' => 'test-resources', 'id' => '12345', 'attributes' => [ 'color' => 'red' ]]]));
        $this->assertInstanceOf('\\CFX\\JsonApi\\ServerRequest', $r, "Should be a JsonApi ServerRequest object");
    }

    public function testParsesUriCorrectly() {
        $r = new ServerRequest("GET", "https://api.kaelshipman.me/test-resources", [ 'Accept' => 'application/vnd.api+json' ]);
        $this->assertTrue($r->isForResourceCollection(), "Should indicate that this request is for the resource collection");
        $this->assertFalse($r->isForPrimaryResource(), "Should indicate that this request is NOT for a primary resource");
        $this->assertFalse($r->isForRelationshipsCollection(), "Should indicate that this request is NOT for the relationships collection");
        $this->assertFalse($r->isForSpecificRelationship(), "Should indicate that this request is NOT for a specific relationship");
        $this->assertFalse($r->isForSpecificRelationshipMember(), "Should indicate that this request is NOT for a specific relationship member");

        $r = new ServerRequest("GET", "https://api.kaelshipman.me/test-resources/12345", [ 'Accept' => 'application/vnd.api+json' ]);
        $this->assertFalse($r->isForResourceCollection(), "Should indicate that this request is NOT for the resource collection");
        $this->assertTrue($r->isForPrimaryResource(), "Should indicate that this request is for a primary resource");
        $this->assertFalse($r->isForRelationshipsCollection(), "Should indicate that this request is NOT for the relationships collection");
        $this->assertFalse($r->isForSpecificRelationship(), "Should indicate that this request is NOT for a specific relationship");
        $this->assertFalse($r->isForSpecificRelationshipMember(), "Should indicate that this request is NOT for a specific relationship member");

        $r = new ServerRequest("GET", "https://api.kaelshipman.me/test-resources/12345/relationships", [ 'Accept' => 'application/vnd.api+json' ]);
        $this->assertFalse($r->isForResourceCollection(), "Should indicate that this request is NOT for the resource collection");
        $this->assertFalse($r->isForPrimaryResource(), "Should indicate that this request is NOT for a primary resource");
        $this->assertTrue($r->isForRelationshipsCollection(), "Should indicate that this request is for the relationships collection");
        $this->assertFalse($r->isForSpecificRelationship(), "Should indicate that this request is NOT for a specific relationship");
        $this->assertFalse($r->isForSpecificRelationshipMember(), "Should indicate that this request is NOT for a specific relationship member");

        $r = new ServerRequest("GET", "https://api.kaelshipman.me/test-resources/12345/relationships/friends", [ 'Accept' => 'application/vnd.api+json' ]);
        $this->assertFalse($r->isForResourceCollection(), "Should indicate that this request is NOT for the resource collection");
        $this->assertFalse($r->isForPrimaryResource(), "Should indicate that this request is NOT for a primary resource");
        $this->assertFalse($r->isForRelationshipsCollection(), "Should indicate that this request is NOT for the relationships collection");
        $this->assertTrue($r->isForSpecificRelationship(), "Should indicate that this request is for a specific relationship");
        $this->assertFalse($r->isForSpecificRelationshipMember(), "Should indicate that this request is NOT for a specific relationship member");

        $r = new ServerRequest("GET", "https://api.kaelshipman.me/test-resources/12345/relationships/friends/54321", [ 'Accept' => 'application/vnd.api+json' ]);
        $this->assertFalse($r->isForResourceCollection(), "Should indicate that this request is NOT for the resource collection");
        $this->assertFalse($r->isForPrimaryResource(), "Should indicate that this request is NOT for a primary resource");
        $this->assertFalse($r->isForRelationshipsCollection(), "Should indicate that this request is NOT for the relationships collection");
        $this->assertTrue($r->isForSpecificRelationship(), "Should indicate that this request is for a specific relationship");
        $this->assertTrue($r->isForSpecificRelationshipMember(), "Should indicate that this request is for a specific relationship member");
    }
}

