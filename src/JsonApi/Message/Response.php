<?php
namespace CFX\JsonApi;

class Response extends \GuzzleHttp\Psr7\Response implements ResponseInterface {
    use MessageTrait;

    public function __construct($status=200, array $headers=[], $body=null, $version='1.1', $reason=null) {
        if ($body) throw new \RuntimeException("The `\$body` paramter may not be used with this class. Instead, you should set the body content by passing a JsonApi Document object to  `withJsonApiDoc`.");
        if (array_key_exists('Content-Type', $headers) && strpos($headers['Content-Type'], 'application/vnd.api+json') === false) throw new BadContentTypeException("Content type must be specified as `application/vnd.api+json` for JsonApi Responses");
        parent::__construct($status, $headers, $body, $version, $reason);
    }
}

