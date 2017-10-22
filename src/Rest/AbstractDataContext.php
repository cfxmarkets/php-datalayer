<?php
namespace CFX\Rest;

abstract class AbstractDataContext extends \CFX\AbstractDataContext implements DataContextInterface {
    // Abstract properties to be overridden by children
    protected static $apiName;
    protected static $apiVersion;

    // Instance properties
    protected $baseUri;
    protected $apiKey;
    protected $apiKeySecret;
    protected $httpClient;

    public function __construct($baseUri, $apiKey, $apiKeySecret, \GuzzleHttp\ClientInterface $httpClient) {
        if (!static::$apiName) throw new \RuntimeException("Programmer: You must define the \$apiName property for your Client.");
        if (static::$apiVersion === null) throw new \RuntimeException("Programmer: You must define the \$apiVersion property for your Client.");

        $this->baseUri = $baseUri;
        $this->apiKey = $apiKey;
        $this->apiKeySecret = $apiKeySecret;
        $this->httpClient = $httpClient;
    }

    public function getBaseUri() {
        return $this->baseUri;
    }

    public function getApiKey() {
        return $this->apiKey;
    }

    public function getApiKeySecret() {
        return $this->apiKeySecret;
    }

    public function getHttpClient() {
        return $this->httpClient;
    }

    public function getApiName() {
        return static::$apiName;
    }

    public function getApiVersion() {
        return static::$apiVersion;
    }
}

