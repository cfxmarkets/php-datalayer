<?php
namespace CFX\JsonApi;

class GenericResource extends AbstractResource implements GenericResourceInterface {
    protected $validAttributes;
    protected $validRelationships;

    public function __construct(DatasourceInterface $datasource, $data=null, $validAttributes=null, $validRelationships=null) {
        $this->datasource = $datasource;

        // Set all attributes to null initially, then to default values
        if ($validAttributes) {
            $this->validAttributes = $validAttributes;
            foreach($this->validAttributes as $attr => $v) {
                if (is_int($attr)) {
                    $attr = $v;
                    $v = null;
                }
                $this->attributes[$attr] = null;
                $this->setAttributes($attr, $v);
            }
        }

        // Set relationships
        if ($validRelationships) {
            $this->validRelationships = $validRelationships;
            $relationships = [];
            foreach($this->validRelationships as $name) $relationships[$name] = $this->datasource->newJsonApiRelationship(['name' => $name]);
        }

        // Check to see if there's data waiting for us in our database
        $this->restoreFromData();

        // If we've passed in initial data, update from that
        if ($data) $this->updateFromData($data);
    }

    public function updateFromData(array $data) {
        // Set ID
        if (array_key_exists('id', $data)) {
            $this->setId($data['id']);
            unset($data['id']);
        }

        // Set Type
        if (array_key_exists('type', $data)) {
            if ($this->resourceType && $data['type'] != $this->resourceType) throw new \InvalidArgumentException("This Resource has a fixed type of `$this->resourceType` that cannot be altered. (You passed a type of `$data[type]`.)");
            $this->resourceType = $data['type'];
            unset($data['type']);
        }

        // Set Attributes
        if (array_key_exists('attributes', $data)) {
            foreach($data['attributes'] as $n => $v) $this->setAttribute($n, $v);
            unset($data['attributes']);
        }

        // Set Relationships
        if (array_key_exists('relationships', $data)) {
            foreach($data['relationships'] as $name => $rel) {
                if (!($rel instanceof RelationshipInterface)) {
                    $rel['name'] = $name;
                    $rel = $this->datasource->newJsonApiRelationship($rel);
                }

                // Finally, set the relationship through it's setter
                $this->setRelationship($rel->getData());
            }
            unset($data['relationships']);
        }

        // Now throw errors on leftover data
        if (count($data) > 0) {
            $e = new MalformedDataException("You have unrecognized data in your JsonApi Resource. Offending keys are: `".implode('`, `', array_keys($data))."`.");
            $e->setOffender("Resource (`$this->resourceType`)");
            $e->setOffendingData($data);
            throw $e;
        }
    }

    public function setAttribute($attr, $val) {
        if ($this->validAttributes && !in_array($attr, $this->validAttributes)) throw new \InvalidArgumentException("Invalid attribute passed: This resource has defined a set of valid attributes which does not include `$attr`. Valid attributes are ".implode(', ', $this->validAttributes).".");
        if (!$this->attributes) $this->attributes = [];
        $this->_setAttribute($attr, $val);
        $this->validateAttribute($attr);
    }

    public function setRelationship($name, $r=null) {
        if ($r && !($r instanceof ResourceInterface) && !($r instanceof ResourceCollectionInterface)) throw new \InvalidArgumentException("Relationships must be set to a Resource, a ResourceCollection or null");
        if ($this->validRelationships && !in_array($r->getName(), $this->validRelationships)) throw new \InvalidArgumentException("Invalid relationship passed: This resource has defined a set of valid relationships which does not include `{$r->getName()}`. Valid relationships are ".implode(', ', $this->validRelationships).".");
        if (!$this->relationships) $this->relationships = [];
        if (!array_key_exists($name, $this->relationships)) $this->relationships[$name] = $this->datasource->newJsonApiRelationship(['name' => $name]);
        $this->relationships[$name]->setData($r);
        $this->validateRelationship($name);
    }

    public function getAttributes() { return $this->attributes ?: []; }
    public function getAttribute($k) {
        if (!$this->attributes || !array_key_exists($k, $this->attributes)) return null;
        return $this->attributes[$k];
    }
    public function getRelationships() { return $this->relationships ?: []; }
    public function getRelationship($k) {
        if ($this->validRelationships && !in_array($k, $this->validRelationships)) throw new UnknownRelationshipException("The relationship you've requested, `$k`, is not a valid relationship on this resource.");
        if (!$this->relationships || !array_key_exists($k, $this->relationships)) $this->setRelationship($this->datasource->newJsonApiRelationship(['name' => $k]));
        return $this->relationships[$k];
    }

    protected function validateRelationship($rel) {}
    protected function validateAttribute($attr) {}
}

