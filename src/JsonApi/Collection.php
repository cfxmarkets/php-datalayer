<?php
namespace CFX\JsonApi;

class Collection implements CollectionInterface  {
    use \KS\ArrayAccessTrait {
        offsetSet as protected parentOffsetSet;
    }
    use \KS\IteratorTrait;
    use \KS\CountableTrait;

    protected $stringIndexable = true;

    public function __construct($items=[]) {
        foreach($items as $k => $v) $this[$k] = $v;
    }

    public function offsetSet($offset, $value) {
        if (!$this->stringIndexable && $offset !== null && !is_int($offset)) throw new \RuntimeException("A JsonApi Collection object cannot be string-indexed. All indexes must be integers. (Offending index: `$offset`.)");
        $this->parentOffsetSet($offset, $value);
    }

    public function jsonSerialize($fullResource=true) {
        $data = [];
        foreach($this->elements as $k => $e) {
            if ($e instanceof \JsonSerializable) $data[$k] = $e->jsonSerialize($fullResource);
            else $data[$k] = $e;
        }
        return $data;
    }

    public function summarize() {
        $str = [];
        foreach($this->elements as $k => $v) $str[] = "$k: ".((string)$v);
        return implode("; ", $str);
    }
}

