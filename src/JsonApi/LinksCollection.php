<?php
namespace CFX\JsonApi;

class LinksCollection extends IndexedCollection implements LinksCollectionInterface {
    public function offsetSet($offset, $value) {
        if (!($value instanceof Link)) throw new \InvalidArgumentException("All values passed to a JsonApi LinksCollection must be JsonApi Links. Value is of type `".get_class($value)."`");
        parent::offsetSet($offset, $value);
    }

    public function summarize() {
        $str = [];
        foreach($this->elements as $link) $str[] = "Link {$link->getMemberName()}: {$link->getHref()}";
        return implode('; ', $str);
    }
}

