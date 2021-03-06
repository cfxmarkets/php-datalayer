<?php
namespace CFX\Persistence\Rest;

abstract class AbstractDataContext extends \CFX\Persistence\AbstractDataContext implements DataContextInterface {
    /**
     * @var string The name of the given REST API (to be overridden by children)
     */
    protected static $apiName;

    /**
     * @var string The version of the given REST API (to be overridden by children)
     */
    protected static $apiVersion;

    /**
     * @var string The base URI for the REST API being accessed
     */
    protected $baseUri;

    /**
     * @var string The API Key for the REST API being accessed
     */
    protected $apiKey;

    /**
     * @var string The Secret for the REST API being accessed
     */
    protected $apiKeySecret;

    /**
     * @var \GuzzleHttp\ClientInterface An HTTP Client to use for handling requests to the REST API
     */
    protected $httpClient;

    /**
     * @var \GuzzleHttp\Message\RequestInterface[] An array that collects the requests this object is making for debugging purposes
     */
    protected $debugRequestLog = [];

    /**
     * @var \GuzzleHttp\Message\ResponseInterface[] An array that collects the responses this object receives for debugging purposes
     */
    protected $debugResponseLog = [];


    /**
     * Construct a new REST DataContext (an "API client")
     *
     * @param string $baseUri
     * @param string $apiKey
     * @param string $apiKeySecret
     * @param \GuzzleHttp\ClientInterface|null $httpClient
     * @return static
     */
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

    /**
     * @inheritdoc
     */
    public function getBaseUri() {
        return $this->baseUri;
    }

    /**
     * @inheritdoc
     */
    public function getApiKey() {
        return $this->apiKey;
    }

    /**
     * @inheritdoc
     */
    public function getApiKeySecret() {
        return $this->getApiSecret();
    }

    /**
     * @inheritdoc
     */
    public function getApiSecret()
    {
        return $this->apiKeySecret;
    }

    /**
     * @inheritdoc
     */
    public function getHttpClient() {
        return $this->httpClient;
    }

    /**
     * @inheritdoc
     */
    public function getApiName() {
        return static::$apiName;
    }

    /**
     * @inheritdoc
     */
    public function getApiVersion() {
        return static::$apiVersion;
    }

    /**
     * @inheritdoc
     */
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

        $basicAuth = "Basic ".base64_encode("{$this->getApiKey()}:{$this->getApiKeySecret()}");
        if ($authz_header === null) {
            $params['headers']['Authorization'] = $basicAuth;
        } else {
            $params["headers"][$authz_header] .= ",$basicAuth";
        }

        $request = new \GuzzleHttp\Psr7\Request($method, $uri, $params['headers']);
        $request = $this->applyRequestOptions($request, $params);
        unset($params['body'], $params['json'], $params['headers'], $params['query']);

        $this->log(\Psr\Log\LogLevel::DEBUG, "REST Data Context calling '{$request->getMethod()} {$request->getUri()}'", [ "headers" => $request->getHeaders() ]);

        $response = $this->processResponse($this->httpClient->send($request, $params));

        if ($this->debug) {
            $this->debugRequestLog[] = $request;
            $this->debugResponseLog[] = $response;
        }

        return $response;
    }

    /**
     * Function to return a new request as transformed by the Guzzle 5.3 options array
     *
     * @param \Psr\Http\Message\RequestInterface $r
     * @param array $options
     * @return \Psr\Http\Message\RequestInterface
     */
    protected function applyRequestOptions(\Psr\Http\Message\RequestInterface $r, array $options = [])
    {
        $body = (string)$r->getBody();
        $bodyType = 'application/x-www-form-urlencoded';
        $query = $r->getUri()->getQuery();
        if ($query !== '') {
            $query = explode("&", $query);
        } else {
            $query = [];
        }
        $uri = $r->getUri();

        if (array_key_exists('body', $options)) {
            if ($body) {
                throw new \RuntimeException("You have supplied a body for this request, but you're also trying to provide a body in the parameters array. You should choose one or the other.");
            }
            if (is_array($options['body'])) {
                $body = http_build_query($options['body']);
            } else {
                $body = $options['body'];
            }
        }

        if (array_key_exists('json', $options)) {
            if ($body) {
                throw new \RuntimeException("You have already supplied a body for this request, but you're also trying to provide a json key in the parameters array. You should choose one or  the other.");
            }
            if (is_string($options['json'])) {
                $body = $options['json'];
            } else {
                $body = json_encode($options['json']);
            }
            $bodyType = 'application/json';
        }

        if (array_key_exists('query', $options)) {
            $newQuery = $options['query'];
            if (is_string($newQuery)) {
                $newQuery = explode("&", $newQuery);
            } else {
                $newQuery = explode("&", http_build_query($newQuery));
            }
            $query = array_merge_recursive($query, $newQuery);
        }

        if (count($query) > 0) {
            $uri = $uri->withQuery(implode("&", $query));
            $r = $r->withUri($uri);
        }

        if ($body) {
            $r= $r->withBody(\GuzzleHttp\Psr7\stream_for($body));
            if (!$r->getHeader('Content-Type')) {
                $r = $r->withHeader('Content-Type', $bodyType);
            }
        }
        return $r;
    }

    /**
     * @inheritdoc
     */
    public function debugGetResponseLog()
    {
        if (!$this->debug) {
            throw new \RuntimeException("In order to get the Response Log, you must enable debugging by using `setDebug`");
        }
        return $this->debugResponseLog;
    }

    /**
     * @inheritdoc
     */
    public function debugClearResponseLog()
    {
        $this->debugResponseLog = [];
    }

    /**
     * @inheritdoc
     */
    public function debugGetRequestLog()
    {
        if (!$this->debug) {
            throw new \RuntimeException("In order to get the Request Log, you must enable debugging by using `setDebug`");
        }
        return $this->debugRequestLog;
    }

    /**
     * @inheritdoc
     */
    public function debugClearRequestLog()
    {
        $this->debugRequestLog = [];
    }







    /**
     * Compose the final URI to which to send the request
     *
     * This method allows descendents to implement URL composition differently while still utilizing the rest of the
     * sendRequest functionality.
     *
     * @return string
     */
    protected function composeUri($endpoint) {
        return $this->getBaseUri()."/{$this->getApiName()}"."/v{$this->getApiVersion()}{$endpoint}";
    }

    /**
     * Process the response from the API call.
     *
     * This method is used to throw fine-grained exceptions in response to various server responses
     *
     * @param \GuzzleHttp\Message\ResponseInterface
     * @return \GuzzleHttp\Message\ResponseInterface
     * @throws \Exception (may throw various exceptions)
     */
    protected function processResponse($r) {
        $this->log(\Psr\Log\LogLevel::DEBUG, "Response returned with code {$r->getStatusCode()} and response body '{$r->getBody()}'");

        if ($r->getStatusCode() >= 500) {
            throw new \RuntimeException("Server Error: ".$r->getBody());
        } elseif ($r->getStatusCode() >= 400) {
            if ($r->getStatusCode() === 404) {
                throw new \CFX\Persistence\ResourceNotFoundException("The resource you're looking for wasn't found in our system");
            } elseif ($r->getStatusCode() === 409) {
                $e = new \CFX\Persistence\DuplicateResourceException("The resource you've tried to create already exists in our system. Use the `getDuplicateResource` method of this exception to access it.");
                $body = json_decode($r->getBody(), true);
                if (
                    isset($body["errors"]) &&
                    is_array($body["errors"]) &&
                    count($body["errors"]) > 0 &&
                    isset($body["errors"][0]["meta"]) &&
                    isset($body["errors"][0]["meta"]["duplicateResource"])
                ) {
                    try {
                        $duplicate = $body['errors'][0]['meta']['duplicateResource'];
                        $e->setDuplicateResource($this->newResource($duplicate, $duplicate['type']));
                    } catch (\CFX\Persistence\UnknownDatasourceException $e) {
                        throw new \RuntimeException("The API has sent back a duplicate resource with a type that this SDK doesn't recognize (type: `$duplicate[type]`). This is an issue that the API Provider needs to resolve.");
                    }
                }
                throw $e;
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

    /**
     * Factory method for instantiating a default HTTP Client
     *
     * @return \GuzzleHttp\ClientInterface
     */
    protected function newHttpClient()
    {
        return new \GuzzleHttp\Client([
            'http_errors' => false,
        ]);
    }
}

