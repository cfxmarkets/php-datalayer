<?php
namespace CFX\Persistence;

abstract class AbstractPhpSessionDatasource extends AbstractArrayDatasource
{
    protected $datastore;
    private $cache;

    public function __construct(DataContextInterface $context, array &$datastore = null)
    {
        if ($datastore === null) {
            if (is_array($_SESSION)) {
                $datastore = &$_SESSION;
            } else {
                $datastore = [];
            }
        }

        return parent::__construct($context, $datastore);
    }

    protected function &getDatastore()
    {
        if ($this->cache === null) {
            if (is_array($this->datastore) && array_key_exists($this->getResourceType(), $this->datastore)) {
                $this->cache = json_decode($this->datastore[$this->getResourceType()], true);
            }
            if (!is_array($this->cache)) {
                $this->cache = [];
            }
        }
        return $this->cache;
    }

    public function __destruct()
    {
        if ($this->cache) {
            $this->datastore[$this->getResourceType()] = json_encode($this->cache);
        }
    }
}


