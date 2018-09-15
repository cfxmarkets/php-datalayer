<?php
namespace CFX\Persistence;

abstract class AbstractArrayDatasource extends AbstractDatasource
{
    protected $datastore;

    public function __construct(DataContextInterface $context, array &$datastore = null)
    {
        if ($datastore === null) {
            $datastore = [];
        }
        $this->datastore = &$datastore;

        return parent::__construct($context);
    }

    public function get($q = null)
    {
        $q = $this->parseDSL($q);
        $datastore = $this->getDatastore();
        if ($q->includes('id')) {
            $results = [];
            foreach ($datastore as $r) {
                if (isset($r['id']) && $r['id'] === $q->getId()) {
                    $results = [$r];
                    break;
                }
            }
            return $this->inflateData($results, false);
        } else {
            return $this->inflateData($datastore, true);
        }
    }

    public function saveNew(\CFX\JsonApi\ResourceInterface $r)
    {
        if (!$r->getId()) {
            $r->setId(md5(uniqid()));
        }
        $this->currentData = [];
        $r->restoreFromData();
        $datastore = &$this->getDatastore();
        $datastore[] = json_decode(json_encode($r), true);
        return $this;
    }

    public function saveExisting(\CFX\JsonApi\ResourceInterface $r)
    {
        $datastore = &$this->getDatastore();
        $saved = false;
        foreach ($datastore as $k => $e) {
            if (isset($e['id']) && $e['id'] === $r->getId()) {
                $datastore[$k] = json_decode(json_encode($r), true);
                $saved = true;
                break;
            }
        }
        if (!$saved) {
            return $this->saveNew($r);
        } else {
            return $this;
        }
    }

    public function delete($r)
    {
        if ($r instanceof \CFX\JsonApi\ResourceInterface) {
            $id = $r->getId();
        } else {
            $id = $r;
        }

        $datastore = &$this->getDatastore();
        foreach($datastore as $k => $e) {
            if (isset($e['id']) && $e['id'] === $r->getId()) {
                unset($datastore[$k]);
                break;
            }
        }
        return $this;
    }

    protected function &getDatastore()
    {
        return $this->datastore();
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
}


