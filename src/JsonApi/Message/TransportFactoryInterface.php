<?php
namespace CFX\JsonApi;

interface TransportFactoryInterface {
    public function newJsonApiServerRequest($method, $uri, array $headers=[], $body=null, $version='1.1', array $serverParams=[]);
    public function jsonApiServerRequestFromGlobals();
    public function newJsonApiResponse($status=200, array $headers=[], $body=null, $version='1.1', $reason=null);
}

