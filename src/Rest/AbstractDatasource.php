<?php
namespace CFX\Rest;

abstract class AbstractDatasource extends \CFX\AbstractDatasource implements DatasourceInterface, \KS\JsonApi\FactoryInterface {
    public function get($q=null) {
        $endpoint = "/".static::$resourceType;
        if ($q) {
            if (substr($q, 0, 3) != 'id=' || strpos($q, ' ') !== false) throw new \RuntimeException("Programmer: for now, only id queries are accepted. Please pass `id=[asset-symbol]` if you'd like to query a specific asset. Otherwise, just get all assets and filter them yourself.");
            $isCollection = false;

            $endpoint .= "/".substr($q, 3);
        } else {
            $isCollection = true;
        }

        $r = $this->sendRequest('GET', $endpoint);
        $obj = json_decode($r->getBody(), true);

        // Convert to "table of rows" format for inflate
        if (!$isCollection) $obj = [$obj];
        $obj = $this->inflateData($obj, $isCollection);
        if (!$isCollection) $obj = $obj[0];

        return $obj;
    }

    public function sendRequest($method, $endpoint, array $params=[]) {
        // Composer URI
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

        if (!$authz_header) $params['headers']['Authorization'] = "Basic ".base64_encode("{$this->context->getApiKey()}:{$this->context->getApiKeySecret()}");

        $r = $this->context->getHttpClient()->createRequest($method, $uri, $params);
        return $this->processResponse($this->context->getHttpClient()->send($r));
    }

    protected function composeUri($endpoint) {
        return $this->context->getBaseUri()."/v".$this->context->getApiVersion().$endpoint;
    }

    protected function processResponse($r) {
        if ($r->getStatusCode() >= 500) throw new \RuntimeException("Server Error: ".$r->getBody());
        elseif ($r->getStatusCode() >= 400) throw new \RuntimeException("User Error: ".$r->getBody());
        elseif ($r->getStatusCode() >= 300) throw new \RuntimeException("Don't know how to handle 3xx codes.");
        elseif ($r->getStatusCode() >= 200) return $r;
        else throw new \RuntimeException("Don't know how to handle 1xx codes.");
    }
}

