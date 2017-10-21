<?php
namespace CFX\JsonApi;

class Relationship implements RelationshipInterface {
    protected $datasource;

    protected $name;
    protected $links;
    protected $meta;
    protected $data;

    public function __construct(DatasourceInterface $datasource, $data) {
        $this->datasource = $datasource;

        if (!array_key_exists('name', $data)) throw new \InvalidArgumentException("To construct a Relationship, you must pass a `name` key containing the name of the resource.");
        $this->name = $data['name'];
        unset($data['name']);

        if (!array_key_exists('data', $data)) $data['data'] = null;

        if ($data['data'] === null) $this->data = null;
        elseif (array_key_exists('id', $data['data'])) $this->data = $this->datasource->newJsonApiResource($data['data'], array_key_exists('type', $data['data']) ? $data['data']['type'] : null);
        else {
            $rc = $this->data = $this->datasource->newJsonApiResourceCollection();
            foreach($data['data'] as $r) $rc[] = $this->datasource->newJsonApiResource($r, array_key_exists('type', $r) ? $r['type'] : null);
        }
        unset($data['data']);

        if (array_key_exists('links', $data)) {
            $this->links = $data['links'];
            unset($data['links']);
        }
        if (array_key_exists('meta', $data)) {
            $this->meta = $data['meta'];
            unset($data['meta']);
        }

        if (count($data) > 0) {
            $e = new MalformedDataException("You have unrecognized data in your JsonApi Relationship. Offending keys are: `".implode('`, `', array_keys($data))."`.");
            $e->setOffender("Relationship (`$this->name`)");
            $e->setOffendingData($data);
            throw $e;
        }
    }

    public function getName() { return $this->name; }
    public function getLinks() { return $this->links; }
    public function getMeta() { return $this->meta; }
    public function getData() { return $this->data; }

    public function setData($d=null) {
        // Typecheck
        if ($d !== null) {
            if (!($d instanceof ResourceInterface) && !($d instanceof ResourceCollectionInterface)) {
                $type = gettype($d);
                if ($type == 'object') $type = get_class($d);
                throw new \InvalidArgumentException("Value passed to `setData` must be either a Resource (`ResourceInterface`), a Resource Collection (`ResourceCollectionInterface`), or null. (`$type` given)");
            }
        }

        $this->data = $d;
        return $this;
    }

    public function getMemberName() { return $this->getName(); }

    public function jsonSerialize() {
        $data = [
            'data' => $this->data ? $this->data->jsonSerialize(false) : null
        ];
        if ($this->links) $data['links'] = $this->links;
        if ($this->meta) $data['meta'] = $this->meta;
        return $data;
    }
}

