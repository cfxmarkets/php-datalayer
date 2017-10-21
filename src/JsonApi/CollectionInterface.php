<?php
namespace CFX\JsonApi;

interface CollectionInterface extends \ArrayAccess, \Iterator, \Countable, \JsonSerializable {
    public function summarize();
}

