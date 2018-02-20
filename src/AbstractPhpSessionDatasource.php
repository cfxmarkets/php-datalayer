<?php
namespace CFX\Persistence;

abstract class AbstractPhpSessionDatasource extends AbstractArrayDatasource
{
    protected $datastore;
    private $cache;

    public function __construct(DataContextInterface $context, array &$datastore = null)
    {
        if ($datastore === null) {
            if (isset($_SESSION) && is_array($_SESSION)) {
                $datastore = &$_SESSION;
            } else {
                $datastore = [];
            }
        }

        return parent::__construct($context, $datastore);
    }

    public function saveNew(\CFX\JsonApi\ResourceInterface $r)
    {
        if (!$r->getId()) {
            $id = session_id();
            if (!$id) {
                throw new \RuntimeException("PhpSessionDatasource needs session_id() to return the correct session id, but it's returning null.");
            } else {
                $r->setId($id);
            }
        }
        return parent::saveNew($r);
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


