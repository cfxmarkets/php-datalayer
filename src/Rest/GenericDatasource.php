<?php
namespace CFX\Persistence\Rest;

/**
 * A generic datasource class that may be used to for REST datasources with no special handling requirements.
 *
 * Accepts all information required to function on construct, including the fully-qualified class name of the resource
 * class it represents and the JSON API resource type designation.
 */
class GenericDatasource extends AbstractDatasource {
    /**
     * @var string The fully-qualified class name of the resource class this datasource represents. This should take the
     * form, `\\CFX\\Brokerage\\MyResource`
     */
    protected $fqcn;

    /**
     * @var array A class map composed on construct
     */
    protected $classMap;

    /**
     * @inheritdoc
     */
    public function getClassMap()
    {
        return $this->classMap;
    }

    /**
     * A method for setting a custom class map
     *
     * @param array $classMap
     * @return static
     */
    public function setClassMap(array $classMap)
    {
        $this->classMap = $classMap;
        return $this;
    }

    /**
     * Construct a GenericDatasource object
     *
     * @param \CFX\Persistence\Rest\DataContextInterface $context
     * @param string $resourceType The JSON API resource type this datasource represents
     * @param string $fqcn The fully-qualified class name of the resource class this datasource represents
     */
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

