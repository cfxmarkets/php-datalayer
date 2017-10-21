<?php
namespace CFX\JsonApi;

class Document implements DocumentInterface {
    protected $version = "1.0";

    protected $baseUrl;
    protected $datasource;
    protected $data;
    protected $errors;
    protected $links;
    protected $meta;
    protected $included;
    protected $jsonapi;

    public function __construct(DatasourceInterface $f, $data=null) {
        $this->datasource = $f;
        if ($data) {
            if (array_key_exists('errors', $data)) {
                foreach($data['errors'] as $error) {
                    if (!($error instanceof Error)) $error = $this->datasource->newJsonApiError($error);
                    $this->addError($error);
                }
                unset($data['errors']);
            }

            if (array_key_exists('data', $data)) {
                if ($data['data'] === null) $this->data = null;
                elseif ($data['data'] instanceof ResourceInterface || $data['data'] instanceof ResourceCollectionInterface) $this->data = $data['data'];
                elseif (is_array($data['data'])) {
                    $isCollection = is_numeric(implode('', array_keys($data['data'])));
                    if ($isCollection) {
                        $rc = $this->datasource->newJsonApiResourceCollection();
                        foreach ($data['data'] as $r) $rc[] = $this->datasource->newJsonApiResource($r, $r['type']);
                        $this->data = $rc;
                    } else {
                        if (!array_key_exists('type', $data['data'])) throw new \InvalidArgumentException("If you provide a resource via the `data` key, you MUST specify its type via the `data::type` key (e.g., [ 'data' => [ 'type' => 'my-resources', 'attributes' => [ ... ] ] ]).");
                        $this->data = $this->datasource->newJsonApiResource($data['data'], $data['data']['type']);
                    }
                } else {
                    throw new \InvalidArgumentException("Malformed `data` object in initial data array.");
                }
                unset($data['data']);
            }

            if (array_key_exists('links', $data)) {
                if (is_array($data['links'])) {
                    $links = $this->datasource->newJsonApiLinksCollection();
                    foreach($data['links'] as $name => $link) {
                        if (!($link instanceof LinkInterface)) {
                            if (is_array($link)) {
                                if (!array_key_exists('name', $link)) $link['name'] = $name;
                            } else {
                                $link = [
                                    'href' => $link,
                                    'name' => $name,
                                ];
                            }
                            $links[$name] = $this->datasource->newJsonApiLink($link);
                        } else {
                            $links[$link->getMemberName()] = $link;
                        }
                    }
                    $this->links = $links;
                } elseif ($data['links'] instanceof LinksCollectionInterface) {
                    $this->links = $data['links'];
                } else {
                    throw new \InvalidArgumentException("Links passed must be either an indexed collection of link objects or an array of link objects or data");
                }
                unset($data['links']);
            }

            if (array_key_exists('meta', $data)) {
                if ($data['meta'] instanceof Meta) $this->meta = $data['meta'];
                else $this->meta = $this->datasource->newJsonApiMeta($data['meta']);
                unset($data['meta']);
            }

            if (array_key_exists('included', $data)) {
                if (!is_array($data['included'])) throw new \InvalidArgumentException("If you pass an array of included resources, it must be an array, not an object or string or null or anything else.");
                $this->included = $this->datasource->newJsonApiResourceCollection();
                foreach($data['included'] as $r) $this->included[] = $this->datasource->newJsonApiResource($r, $r['type']);
                unset($data['included']);
            }

            if (array_key_exists('jsonapi', $data)) {
                $this->jsonapi = $data['jsonapi'];
                unset($data['jsonapi']);
            }

            if (count($data) > 0) {
                $e = new MalformedDataException("You have unrecognized data in your JsonApi document. Offending keys are: `".implode('`, `', array_keys($data))."`.");
                $e->setOffender("Document");
                $e->setOffendingData($data);
                throw $e;
            }
        }

        if (!$this->jsonapi) $this->jsonapi = ['version' => $this->version];
    }


    public function getBaseUrl() { return $this->baseUrl; }
    public function getData() { return $this->data; }
    public function getErrors() { return $this->errors ?: $this->datasource->newJsonApiErrorsCollection(); }
    public function getLinks() { return $this->links ?: $this->datasource->newJsonApiLinksCollection(); }
    public function getLink($name) {
        if ($this->links) return $this->links[$name];
        else return null;
    }
    public function getMeta() { return $this->meta; }




    public function setBaseUrl($url) {
        $this->baseUrl = $url;
    }

    public function setData($data) {
        if (!($data instanceof ResourceInterface) && !($data instanceof ResourceCollectionInterface)) throw new \InvalidArgumentException("Data must be either a Resource or a ResourceCollection");
        $this->data = $data;
        return $this;
    }

    public function addError(ErrorInterface $e) {
        if (!$this->errors) $this->errors = $this->datasource->newJsonApiErrorsCollection();
        $this->errors[] = $e;
        return $this;
    }

    public function addLink(LinkInterface $l) {
        if (!$this->links) $this->links = $this->datasource->newJsonApiLinksCollection();
        $this->links[$l->getMemberName()] = $l;
        return $this;
    }

    public function setMeta(MetaInterface $m) {
        $this->meta = $m;
        return $this;
    }




    // TODO: Make it so that jsonSerialize doesn't alter the object (currently it adds a self link to the object if none exists)
    public function jsonSerialize() {
        $data = [];
        if ($this->errors) $data['errors'] = $this->errors;
        else {
            $data['data'] = $this->data;
            if ((!$this->links || !$this->getLink('self')) && $this->data) {
                if ($this->data instanceof ResourceInterface) {
                    $this->addLink($this->datasource->newJsonApiLink([ 'name' => 'self', 'href' => $this->baseUrl.$this->data->getSelfLinkPath() ]));
                } elseif ($this->data instanceof ResourceCollectionInterface && count($this->data)) {
                    $this->addLink($this->datasource->newJsonApiLink([ 'name' => 'self', 'href' => $this->baseUrl.$this->data[0]->getCollectionLinkPath() ]));
                }
            }
        }

        if ($this->links) $data['links'] = $this->links;
        if ($this->meta) $data['meta'] = $this->meta;

        $data['jsonapi'] = $this->jsonapi;

        return $data;
    }
}

