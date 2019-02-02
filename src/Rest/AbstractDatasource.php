<?php
namespace CFX\Persistence\Rest;

abstract class AbstractDatasource extends \CFX\Persistence\AbstractDatasource implements DatasourceInterface {
    /**
     * @inheritdoc
     */
    public function get($filter=null, ?string $sort = null, ?array $pagination = null) {
        $endpoint = "/".$this->getResourceType();
        if (preg_match("/^id ?= ?([a-zA-Z0-9:|_-]+)$/", trim($filter), $matches)) {
            $endpoint .= "/".$matches[1];
            $filter = null;
        }

        $query = [];
        if ($filter) {
            $query[] = "q=".urlencode($filter);
        }
        if ($sort) {
            $query[] = "sort=".urlencode($sort);
        }
        if ($pagination) {
            foreach ($pagination as $param => $val) {
                $query[] = "$param=".urlencode($val);
            }
        }

        if (count($query) > 0) {
            $endpoint .= "?".implode("&", $query);
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
     * This method exists to allow datasources to set default parameters for their requests.
     * For example, certain datasources may always use an OAuth token for authorization,
     * and that can be set here before the request is sent to the context for processing.
     *
     * @see \CFX\Persistence\Rest\DataContextInterface::sendRequest
     */
    protected function sendRequest($method, $endpoint, array $params=[]) {
        return $this->context->sendRequest($method, $endpoint, $params);
    }

    /**
     * Overridden because this functionality is not necessary in the context of a default API client
     *
     * @inheritdoc
     */
    public function convert(\CFX\JsonApi\ResourceInterface $src, $convertTo) {
        return $src;
    }
}

