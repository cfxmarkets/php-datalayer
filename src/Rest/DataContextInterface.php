<?php
namespace CFX\Persistence\Rest;

interface DataContextInterface {
    public function getApiName();
    public function getApiVersion();
    public function getBaseUri();
    public function getApiKey();
    public function getApiKeySecret();
    public function getHttpClient();

    /**
     * sendRequest -- send a request for data, returning either raw data, an object, or a collection
     *
     * @param string $method A standard HTTP Method string
     * @param string $endpoint A REST endpoint WITH leading slash, but WITHOUT trailing slash
     * @param array $params an array of request parameters (@see \GuzzleHttp\Message\RequestInterface)
     */
    public function sendRequest($method, $endpoint, array $params=[]);
}

