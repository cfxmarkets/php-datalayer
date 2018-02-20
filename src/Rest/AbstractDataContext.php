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
    protected $apiSecret;

    /**
     * @var \GuzzleHttp\ClientInterface An HTTP Client to use for handling requests to the REST API
     */
    protected $httpClient;

    /**
     * @var array An array that collects the request/response transactions for debugging purposes
     */
    private $transactionLog = [];






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

    public function logTransaction(string $source, \Psr\Http\Message\RequestInterface $request, ?\Psr\Http\Message\ResponseInterface $response) {
        if ($this->debug) {
            $this->transactionLog[microtime()] = [$request, $response];
            parent::logQuery($source, "Request: $request; Response: $response");
        }
        return $this;
    }

    public function getTransactionLog()
    {
        return $this->transactionLog;
    }
}

