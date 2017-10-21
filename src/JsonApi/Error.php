<?php
namespace CFX\JsonApi;

class Error implements ErrorInterface {
    protected $fields = ['id','status','code','title','detail','source','links','meta'];

    protected $id;
    protected $links;
    protected $status;
    protected $code;
    protected $title;
    protected $detail;
    protected $source;
    protected $meta;

    public function __construct(array $props) {
        if (!array_key_exists('status', $props)) throw new \InvalidArgumentException("You must include a `status` key in your initial properties array");
        if (!array_key_exists('title', $props)) throw new \InvalidArgumentException("You must include a `title` key in your initial properties array");

        $invalidData = [];

        foreach ($props as $k => $v) {
            if (!in_array($k, $this->fields)) {
                $invalidData[$k] = $v;
                continue;
            }

            if ($k == 'links') {
                if (!($v instanceof Collection)) throw new \InvalidArgumentException("Value passed with key `links` must be a JsonApi Collection containing an `about` key with a link to more information about this error.");
                if (count($v) != 1 ||
                    !array_key_exists('about', $v) ||
                    (!is_string($v['about']) && !($v['about'] instanceof Link))) throw new \InvalidArgumentException("The Collection passed as `links` must contain exactly one item, `about`, which should be a string or a Link object.");
            } elseif ($k == 'status') {
                if (!is_int($v) || $v < 100 || $v >= 600) throw new \InvalidArgumentException("Value for `status` must be an integery between 100 and 599");
            }

            // TODO: Finish field validations

            $this->$k = $v;
        }

        if (count($invalidData) > 0) {
            $e = new MalformedDataException("Unrecognized properties: `".implode('`, `', array_keys($invalidData))."`");
            $e->setOffender("Error (`$this->title`)");
            $e->setOffendingData($invalidData);
            throw $e;
        }
    }


    public function getId() { return $this->id; }
    public function getLinks() { return $this->links; }
    public function getStatus() { return $this->status; }
    public function getCode() { return $this->code; }
    public function getTitle() { return $this->title; }
    public function getDetail() { return $this->detail; }
    public function getSource() { return $this->source; }
    public function getMeta() { return $this->meta; }


    public function jsonSerialize() {
        $data = [];
        foreach($this->fields as $field) {
            if ($this->$field) $data[$field] = $this->$field;
        }
        return $data;
    }
}

