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

    /**
     * @var \GuzzleHttp\Message\RequestInterface[] An array that collects the requests this object is making for debugging purposes
     */
    protected $debugRequestLog = [];

    /**
     * @var \GuzzleHttp\Message\ResponseInterface[] An array that collects the responses this object receives for debugging purposes
     */
    protected $debugResponseLog = [];


    public function __construct($baseUri, $apiKey, $apiKeySecret, \GuzzleHttp\ClientInterface $httpClient = null) {
        if (!static::$apiName) throw new \RuntimeException("Programmer: You must define the \$apiName property for your Client.");
        if (static::$apiVersion === null) throw new \RuntimeException("Programmer: You must define the \$apiVersion property for your Client.");

        if (!$httpClient) {
            $httpClient = $this->newHttpClient();
        }

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

        $request = $this->httpClient->createRequest($method, $uri, $params);
        $response = $this->processResponse($this->httpClient->send($request));

        if ($this->debug) {
            $this->debugRequestLog[] = $request;
            $this->debugResponseLog[] = $response;
        }

        return $response;
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
        } elseif ($r->getStatusCode() >= 300) {
            throw new \RuntimeException("Don't know how to handle 3xx codes.");
        } elseif ($r->getStatusCode() >= 200) {
            return $r;
        } else {
            throw new \RuntimeException("Don't know how to handle 1xx codes.");
        }
    }

    protected function newHttpClient()
    {
        return new \GuzzleHttp\Client([
            'defaults' => [
                'config' => [
                    'curl' => [
                        CURLOPT_SSL_VERIFYHOST => 0,
                        CURLOPT_SSL_VERIFYPEER => false,
                    ],
                ],
                'exceptions' => false,
            ]
        ]);
    }

    /**
     * For debugging, get an array of all Resposnes this object has received since the last time the log was cleared
     *
     * @return \GuzzleHttp\Message\ResponseInterface[] An array of all responses that this object has received
     */
    public function debugGetResponseLog()
    {
        if (!$this->debug) {
            throw new \RuntimeException("In order to get the Response Log, you must enable debugging by using `setDebug`");
        }
        return $this->debugResponseLog;
    }

    /**
     * For debugging, clear the response log, so you can see all responses returned in a defined piece of code
     */
    public function debugClearResponseLog()
    {
        $this->debugResponseLog = [];
    }

    /**
     * For debugging, get an array of all Requests this object has made since the last time the log was cleared
     *
     * @return \GuzzleHttp\Message\RequestInterface[] An array of all requests that this object has generated
     */
    public function debugGetRequestLog()
    {
        if (!$this->debug) {
            throw new \RuntimeException("In order to get the Request Log, you must enable debugging by using `setDebug`");
        }
        return $this->debugRequestLog;
    }

    /**
     * For debugging, clear the response log, so you can see all requests returned in a defined piece of code
     */
    public function debugClearRequestLog()
    {
        $this->debugRequestLog = [];
    }
}

