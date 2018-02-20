<?php
namespace CFX\Persistence\Rest;

abstract class AbstractDatasource extends \CFX\Persistence\AbstractDatasource implements DatasourceInterface {
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
    protected $apiSecret;

    /**
     * @var \GuzzleHttp\ClientInterface An HTTP Client to use for handling requests to the REST API
     */
    protected $httpClient;





    /**
     * Construct a new REST DataSource (an "API client")
     *
     * @param string $baseUri
     * @param string $apiKey
     * @param string $apiSecret
     * @param \GuzzleHttp\ClientInterface|null $httpClient
     * @return static
     */
    public function __construct($baseUri, $apiKey, $apiSecret, \GuzzleHttp\ClientInterface $httpClient = null) {
        if (!static::$apiName) throw new \RuntimeException("Programmer: You must define the \$apiName property for your Client.");
        if (static::$apiVersion === null) throw new \RuntimeException("Programmer: You must define the \$apiVersion property for your Client.");

        if (!$httpClient) {
            $httpClient = $this->newHttpClient();
        }

        $this->baseUri = $baseUri;
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
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
    public function getApiSecret()
    {
        return $this->apiSecret;
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
    protected function sendRequest($method, $endpoint, array $params=[]) {
        // Compose URI
        $uri = $this->composeUri($endpoint);

        $params = $this->addAuthParams($params);

        $request = new \GuzzleHttp\Psr7\Request($method, $uri, $params['headers']);
        $request = $this->applyRequestOptions($request, $params);
        unset($params['body'], $params['json'], $params['headers'], $params['query']);

        $response = $this->httpClient->send($request, $params);

        $this->context->logTransaction("REST Datasource `{$this->getResourceType()}`", $request, $response);
        $response = $this->processResponse($response);
        return $response;
    }

    protected function addAuthParams(array $params)
    {
        if (!array_key_exists('headers', $params)) $params['headers'] = [];
        $authz_header = null;
        foreach($params['headers'] as $n => $v) {
            if (strtolower($n) == 'authorization') {
                $authz_header = $n;
                break;
            }
        }

        if (!$authz_header) {
            $params['headers']['Authorization'] = "Basic ".base64_encode("{$this->getApiKey()}:{$this->getApiSecret()}");
        }
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
        if ($r->getStatusCode() >= 500) {
            throw new \RuntimeException("Server Error: ".$r->getBody());
        } elseif ($r->getStatusCode() >= 400) {
            if ($r->getStatusCode() === 404) {
                throw new \CFX\Persistence\ResourceNotFoundException("The resource you're looking for wasn't found in our system");
            } elseif ($r->getStatusCode() === 409) {
                $body = json_decode($r->getBody(), true);
                $duplicate = $body['errors'][0]['meta']['duplicateResource'];
                $e = new \CFX\Persistence\DuplicateResourceException("The resource you've tried to create already exists in our system. Use the `getDuplicateResource` method of this exception to access it.");
                try {
                    $e->setDuplicateResource($this->newResource($duplicate, $duplicate['type']));
                } catch (\CFX\Persistence\UnknownDatasourceException $e) {
                    throw new \RuntimeException("The API has sent back a duplicate resource with a type that this SDK doesn't recognize (type: `$duplicate[type]`). This is an issue that the API Provider needs to resolve.");
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
     * @inheritdoc
     */
    public function get($q=null) {
        $endpoint = "/".$this->getResourceType();
        if (preg_match("/^id ?= ?([a-zA-Z0-9:|_-]+)$/", trim($q), $matches)) {
            $endpoint .= "/".$matches[1];
            $q = null;
        }

        if ($q) {
            $endpoint .= "?q=".urlencode($q);
        }

        $r = $this->sendRequest('GET', $endpoint);
        $obj = json_decode($r->getBody(), true);
        if (!$obj && $this->debug) {
            throw new \RuntimeException("Uh oh! The CFX Api Server seems to have screwed up. It didn't return valid json data. Here's what it returned:\n\n".$r->getBody());
        }
        $obj = $obj['data'];

        // The rest API will either return a single resource or an array of resources. Use that fact to
        // determine how to proceed
        if ($obj === null) {
            throw new \CFX\Persistence\ResourceNotFoundException("The REST API returned null for the requested query.");
        }

        $collection = !array_key_exists('type', $obj);

        // Turn the result into the "collection of rows" that the inflate function is expecting, if necessary
        if (!$collection) {
            $obj = [ $obj ];
        }

        $obj = $this->inflateData($obj, $collection);

        return $obj;
    }

    /**
     * @inheritdoc
     */
    public function getDuplicate(\CFX\JsonApi\ResourceInterface $r)
    {
        throw new \CFX\Persistence\ResourceNotFoundException("Not searching for duplicate resource.");
    }

    /**
     * @inheritdoc
     */
    protected function saveNew(\CFX\JsonApi\ResourceInterface $r) {
        return $this->_saveRest('POST', "/".$this->getResourceType(), $r, false);
    }

    /**
     * @inheritdoc
     */
    protected function saveExisting(\CFX\JsonApi\ResourceInterface $r) {
        return $this->_saveRest('PATCH', "/".$this->getResourceType()."/{$r->getId()}", $r, true);
    }

    /**
     * Convenience method for handling saveNew and saveExisting method calls, which are virtually
     * the same in REST with the exception of method and endpoint
     *
     * @param string $method The HTTP method to use for the request
     * @param string $endpoint The endpoint to which to direct the request
     * @param \CFX\JsonApi\ResourceInterface $r The resource to save
     * @param bool $justChanges Whether to send the whole object or just the changes
     */
    protected function _saveRest($method, $endpoint, \CFX\JsonApi\ResourceInterface $r, $justChanges = false) {
        if ($justChanges) {
            $data = $r->getChanges();
        } else {
            $data = $r;
        }
        $response = $this->sendRequest($method, $endpoint, [ 'json' => [ 'data' => $data ] ]);

        // Use returned data to update resource
        $data = json_decode($response->getBody(), true)['data'];
        if (!$data && $this->debug) {
            throw new \RuntimeException("Uh oh! The CFX Api Server seems to have screwed up. It didn't return valid json data. Here's what it returned:\n\n".$response->getBody());
        }
        $this->currentData = $data;
        $r->restoreFromData();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function delete($r) {
        if ($r instanceof \CFX\JsonApi\ResourceInterface) $r = $r->getId();
        $this->sendRequest('DELETE', "/".$this->getResourceType()."/$r");
        return $this;
    }

    /**
     * Overridden because this functionality is not necessary in the context of a default API client (since APIs are always "public")
     *
     * @inheritdoc
     */
    public function convert(\CFX\JsonApi\ResourceInterface $src, $convertTo) {
        return $src;
    }
}

