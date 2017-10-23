<?php
namespace CFX\Persistence\Rest;

abstract class AbstractDatasource extends \CFX\Persistence\AbstractDatasource implements DatasourceInterface {
    public function get($q=null) {
        $endpoint = "/".static::$resourceType;
        $q = $this->parseQuery($q);
        if (!$q->requestingCollection()) $endpoint .= "/".substr($q, 3);

        $r = $this->sendRequest('GET', $endpoint);
        $obj = json_decode($r->getBody(), true);

        // Convert to "table of rows" format for inflate
        if (!$q->requestingCollection()) $obj = [$obj];
        $obj = $this->inflateData($obj, $isCollection);
        if (!$q->requestingCollection()) $obj = $obj[0];

        return $obj;
    }

    protected function saveNew(\CFX\JsonApi\ResourceInterface $r) {
        return $this->_saveRest('POST', "/".static::$resourceType, $r);
    }

    protected function saveExisting(\CFX\JsonApi\ResourceInterface $r) {
        return $this->_saveRest('PATCH', "/".static::$resourceType."/{$r->getId()}", $r);
    }

    /**
     * Convenience method for handling saveNew and saveExisting method calls, which are virtually
     * the same in REST with the exception of method and endpoint
     */
    protected function _saveRest($method, $endpoint, \CFX\JsonApi\ResourceInterface $r) {
        $r = $this->sendRequest($method, $endpoint, [ 'json' => [ 'data' => $r ] ]);

        // Convert returned data into a "row" for the inflateData function to handle
        $row = [ json_decode($r->getBody(), true) ];
        $row = $this->inflateData($row, false);

        // Return resource
        return $row[0];
    }

    public function delete($r) {
        if ($r instanceof \CFX\JsonApi\ResourceInterface) $r = $r->getId();
        $this->sendRequest('DELETE', "/".static::$resourceType."/$r");
        return $this;
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
        return $this->context->getBaseUri()."/{$this->context->getApiName()}"."/v{$this->context->getApiVersion()}{$endpoint}";
    }

    protected function processResponse($r) {
        if ($r->getStatusCode() >= 500) throw new \RuntimeException("Server Error: ".$r->getBody());
        elseif ($r->getStatusCode() >= 400) throw new \RuntimeException("User Error: ".$r->getBody());
        elseif ($r->getStatusCode() >= 300) throw new \RuntimeException("Don't know how to handle 3xx codes.");
        elseif ($r->getStatusCode() >= 200) return $r;
        else throw new \RuntimeException("Don't know how to handle 1xx codes.");
    }
}

