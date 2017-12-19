<?php
namespace CFX\Persistence\Rest;

interface DataContextInterface {
    /**
     * Get the name of the API this client represents
     * @return string
     */
    public function getApiName();

    /**
     * Get the version of the API this client represents
     * @return string
     */
    public function getApiVersion();

    /**
     * Get the client's base uri
     * @return string
     */
    public function getBaseUri();

    /**
     * Get the client's API Key
     * @return string
     */
    public function getApiKey();

    /**
     * Get the client's API Secret
     * @deprecated
     * @use getApiSecret
     * @return string
     */
    public function getApiKeySecret();

    /**
     * Get the client's API Secret
     * @return string
     */
    public function getApiSecret();

    /**
     * Get the HTTP client used to make requests
     * @return string
     */
    public function getHttpClient();

    /**
     * sendRequest -- send a request for data, returning either raw data, an object, or a collection
     *
     * @param string $method A standard HTTP Method string
     * @param string $endpoint A REST endpoint WITH leading slash, but WITHOUT trailing slash
     * @param array $params an array of request parameters (@see \GuzzleHttp\Message\RequestInterface)
     */
    public function sendRequest($method, $endpoint, array $params=[]);

    /**
     * For debugging, get an array of all Resposnes this object has received since the last time the log was cleared
     *
     * @return \GuzzleHttp\Message\ResponseInterface[] An array of all responses that this object has received
     */
    public function debugGetResponseLog();

    /**
     * For debugging, clear the response log, so you can see all responses returned in a defined piece of code
     */
    public function debugClearResponseLog();

    /**
     * For debugging, get an array of all Requests this object has made since the last time the log was cleared
     *
     * @return \GuzzleHttp\Message\RequestInterface[] An array of all requests that this object has generated
     */
    public function debugGetRequestLog();

    /**
     * For debugging, clear the response log, so you can see all requests returned in a defined piece of code
     */
    public function debugClearRequestLog();
}

