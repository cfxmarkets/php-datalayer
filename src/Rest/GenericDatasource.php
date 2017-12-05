<?php
namespace CFX\Persistence\Rest;

class GenericDatasource extends AbstractDatasource {
    protected $fqcn;
    protected $classMap;

    public function getClassMap()
    {
        return $this->classMap;
    }

    public function setClassMap(array $classMap)
    {
        $this->classMap = $classMap;
        return $this;
    }

    public function __construct(DataContextInterface $context, $resourceType, $fqcn) {
        $this->resourceType = $resourceType;
        $this->fqcn = $fqcn;
        $this->classMap = [
            'private' => $this->fqcn,
            'public' => $this->fqcn,
        ];
        parent::__construct($context);
    }
}

