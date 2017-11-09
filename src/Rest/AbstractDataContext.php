<?php
namespace CFX\Persistence\Rest;

abstract class AbstractDataContext extends \CFX\Persistence\AbstractDataContext implements DataContextInterface {
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

    public function sendRequest($method, $endpoint, array $params=[]) {
        // Compose URI
        $uri = $this->composeUri($endpoint);

        // Add Authorization header if necessary

        if (!array_key_exists('headers', $params)) $params['headers'] = [];
        $authz_header = null;
        foreach($params['headers'] as $n => $v) {
            if (strtolower($n) == 'authorization') {
                $authz_header = $n;
                break;
            }
        }

        if (!$authz_header) $params['headers']['Authorization'] = "Basic ".base64_encode("{$this->getApiKey()}:{$this->getApiKeySecret()}");

        $r = $this->httpClient->createRequest($method, $uri, $params);
        return $this->processResponse($this->httpClient->send($r));
    }

    protected function composeUri($endpoint) {
        return $this->getBaseUri()."/{$this->getApiName()}"."/v{$this->getApiVersion()}{$endpoint}";
    }

    protected function processResponse($r) {
        if ($r->getStatusCode() >= 500) {
            throw new \RuntimeException("Server Error: ".$r->getBody());
        } elseif ($r->getStatusCode() >= 400) {
            if ($r->getStatusCode() === 404) {
                throw new \CFX\Persistence\ResourceNotFoundException("The resource you're looking for wasn't found in our system");
            }
            throw new \RuntimeException("User Error: ".$r->getBody());
        } elseif ($r->getStatusCode() >= 302) {
            throw new \RuntimeException("Don't know how to handle 3xx codes.");
        } elseif ($r->getStatusCode() >= 200) {
            return $r;
        } else {
            throw new \RuntimeException("Don't know how to handle 1xx codes.");
        }
    }
}

