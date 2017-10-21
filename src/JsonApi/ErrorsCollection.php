<?php
namespace CFX\JsonApi;

class ErrorsCollection extends Collection implements ErrorsCollectionInterface {
    protected $stringIndexable = false;

    public function offsetSet($offset, $value) {
        if (!($value instanceof Error)) throw new \InvalidArgumentException("All values passed to a JsonApi ErrorsCollection must be JsonApi Errors. Value is of type `".get_class($value)."`");
        parent::offsetSet($offset, $value);
    }

    public function summarize() {
        $str = [];
        foreach($this->elements as $error) $str[] = "Error {$error->getStatus()} ({$error->getTitle()}): {$error->getDetail()}";
        return implode('; ', $str);
    }
}

