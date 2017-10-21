<?php
namespace CFX\JsonApi;

class ServerRequest extends \GuzzleHttp\Psr7\ServerRequest implements ServerRequestInterface {
    use MessageTrait, ServerRequestTrait;

    public function __construct($method, $uri, array $headers=[], $body=null, $version='1.1', array $serverParams=[]) {
        parent::__construct($method, $uri, $headers, $body, $version, $serverParams);
        $this->parsePath(explode('/', trim($this->getUri()->getPath(), '/')));
    }
}

