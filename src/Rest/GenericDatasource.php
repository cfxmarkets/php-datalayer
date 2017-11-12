<?php
namespace CFX\Persistence\Rest;

class GenericDatasource extends AbstractDatasource {
    protected $fqcn;

    public function __construct(DataContextInterface $context, $resourceType, $fqcn) {
        $this->resourceType = $resourceType;
        $this->fqcn = $fqcn;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data=null, $type = null) {
        $fqcn = $this->fqcn;
        return new $fqcn($this, $data);
    }
}

