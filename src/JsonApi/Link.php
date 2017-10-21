<?php
namespace CFX\JsonApi;

class Link implements LinkInterface {
    protected $f;
    protected $name;
    protected $href;
    protected $meta;

    public function __construct(DatasourceInterface $f, $data=null) {
        $this->f = $f;
        if ($data) {
            if (array_key_exists('name', $data)) {
                if ($data['name'] !== null && !is_string($data['name'])) throw new \InvalidArgumentException("Name must be a string");
                $this->name = $data['name'];
                unset($data['name']);
            }
            if (array_key_exists('href', $data)) {
                if ($data['href'] !== null && !is_string($data['href'])) throw new \InvalidArgumentException("Href must be a string");
                $this->href = $data['href'];
                unset($data['href']);
            }
            if (array_key_exists('meta', $data)) {
                if ($data['meta'] !== null) {
                    if (is_array($data['meta'])) $this->meta = $this->f->newJsonApiMeta($data['meta']);
                    elseif ($data['meta'] instanceof MetaInterface) $this->meta = $data['meta'];
                    else throw new \InvalidArgumentException("Meta must be an array representation of a meta object or an official \\KS\\JsonApi\\Meta object");
                }
                unset($data['meta']);
            }

            if (count($data) > 0) {
                $e = new MalformedDataException("You have unrecognized data in your JsonApi Link. Offending keys are: `".implode('`, `', array_keys($data))."`.");
                $e->setOffender("Link (`$this->name`)");
                $e->setOffendingData($data);
                throw $e;
            }
        }
    }

    public function getName() { return $this->name; }
    public function setName($name) { $this->name = $name; return $this; }
    public function getMemberName() { return $this->getName(); }

    public function getHref() { return $this->href; }
    public function setHref($href) { $this->href = $href; return $this; }

    public function getMeta() { return $this->meta; }
    public function setMeta(MetaInterface $meta) { $this->meta = $meta; return $this; }

    public function jsonSerialize() {
        if ($this->meta) {
            return [
                'href' => $this->href,
                'meta' => $this->meta,
            ];
        } else {
            return $this->href;
        }
    }
}

