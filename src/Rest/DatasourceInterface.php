<?php
namespace CFX\Rest;

interface DatasourceInterface extends \CFX\DatasourceInterface {
    /**
     * sendRequest -- send a request for data, returning either raw data, an object, or a collection
     *
     * @param string $method A standard HTTP Method string
     * @param string $endpoint A REST endpoint WITH leading slash, but WITHOUT trailing slash
     * @param array $params an array of request parameters (@see \GuzzleHttp\Message\RequestInterface)
     */
    public function sendRequest($method, $endpoint, array $params=[]);
}

