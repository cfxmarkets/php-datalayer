<?php
namespace CFX\Persistence;

abstract class AbstractDatasource implements DatasourceInterface {
    /**
     * @var string The json api resource type that this datasource deals in
     */
    protected $resourceType;

    /**
     * @var \CFX\Persistence\DataContextInterface The data context in which this datasource resides
     */
    protected $context;

    /**
     * @var array A JSON API-format array representing data that is prepared for a resource object to extract
     * on initialization.
     */
    protected $currentData;

    /**
     * @var bool A debug flag to help in debugging
     */
    protected $debug = false;




    /**
     * Construct a datasource
     *
     * @param \CFX\Persistence\DataContextInterface $context The data context in which this datasource resides
     * @return static
     */
    public function __construct(DataContextInterface $context) {
        if ($this->getResourceType() === null) throw new \RuntimeException("Programmer: You need to define this subclient's `\$resourceType` attribute. This should match the type of resources that this client deals in.");
        $this->context = $context;
    }

    /**
     * A method for getting mappings between types of objects and the classes that implement them.
     *
     * This method is used by the native `convert` and `create` methods. The minimum expected mappings are
     * `public` and `private`, and these will usually look something like this:
     *
     *     [
     *         'public' => "\\CFX\\Brokerage\\User",
     *         'private' => "\\CFX\\Brokerage\\UserPrivate",
     *     ]
     *
     * @return array The mapping of type names to fully-qualified class names
     */
    abstract public function getClassMap();


    /**
     * @inheritdoc
     */
    public function create(array $data=null, $type = null) {
        if (!$type) {
            $type = 'private';
        }

        $class = $this->getClassMap();
        if (array_key_exists($type, $class)) {
            $class = $class[$type];
            return new $class($this, $data);
        } else {
            throw new \RuntimeException("Programmer: Don't know how to handle classes of type `$type` for `".get_class($this)."` datasources.");
        }
    }


    /**
     * @inheritdoc
     */
    public function convert(\CFX\JsonApi\ResourceInterface $src, $convertTo) {
        $class = $this->getClassMap();
        if (array_key_exists($convertTo, $class)) {
            $class = $class[$convertTo];
            return $class::fromResource($src);
        } else {
            throw new \RuntimeException("Programmer: Don't know how to convert resources to type `$convertTo` for `".get_class($this)."` datasources.");
        }
    }


    /**
     * @inheritdoc
     */
    public function newCollection(array $data=null) {
        return new \CFX\JsonApi\ResourceCollection($data);
    }


    /**
     * @inheritdoc
     */
    public function getResourceType() {
        return $this->resourceType;
    }



    /**
     * @inheritdoc
     */
    public function getCurrentData() {
        $data = $this->currentData;
        $this->currentData = null;
        return $data;
    }


    /**
     * @inheritdoc
     */
    public function save(\CFX\JsonApi\ResourceInterface $r) {
        // If we're trying to save with errors, throw exception
        if ($r->hasErrors()) {
            $errors = $r->getErrors();
            foreach($errors as $k => $v) {
                $errors[$k] = "{$v->getTitle()}: {$v->getDetail()}";
            }
            $e = new \CFX\JsonApi\BadInputException("Bad input:\n\n * ".implode("\n * ", $errors));
            $e->setInputErrors($r->getErrors());
            throw $e;
        }

        // If it exists already, update it
        if ($r->getId()) $this->saveExisting($r);

        // Else, create it
        else {
            try {
                $duplicate = $this->getDuplicate($r);
                if (!($duplicate instanceof \CFX\JsonApi\ResourceInterface)) {
                    $type = gettype($duplicate);
                    if ($type === 'object') {
                        $type = get_class($duplicate);
                    } else {
                        $type .= " ($duplicate)";
                    }
                    throw new \RuntimeException("Programmer: Your `getDuplicate` function has returned something other than a Resource: `$type`");
                }
                throw (new \CFX\Persistence\DuplicateResourceException("You've tried to submit a `{$r->getResourceType()}` resource that's already in our database (duplicate id `{$duplicate->getId()}`)."))
                    ->setDuplicateResource($duplicate);
            } catch (\CFX\Persistence\ResourceNotFoundException $e) {
                $this->saveNew($r);
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function inflateRelated(array $data) {
        return $this->context->newResource($data, array_key_exists('type', $data) ? $data['type'] : null);
    }

    /**
     * @inheritdoc
     */
    public function getRelated($name, $id) {
        try {
            return $this->context->datasourceForType($name)->get("id=$id");
        } catch (UnknownDatasourceException $e) {
            throw new UnknownDatasourceException(
                "Don't know how to get resources of type `$name`. Are you sure this is a related resource? (Hint: You ".
                "may need to override the default `getRelated` method by defining a new one in `".get_class($this)."`. ".
                "You should handle `$name` there, then pass other calls on to the parent method.)"
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function initializeResource(\CFX\JsonApi\ResourceInterface $r) {
        if (!$r->getId()) {
            return $this;
        }

        $targ = $this->get("id=".$r->getId());
        $this->currentData = $targ->jsonSerialize();
        $r->restoreFromData();
        return $this;
    }

    /**
     * Find any resources in the database that match the provided resource
     *
     * This method is usually used to ensure uniqueness
     *
     * @param \CFX\JsonApi\ResourceInterface $r The resource to verify
     * @return \CFX\JsonApi\ResourceInterface The duplicate resource, if one exists
     * @throws \CFX\Persistence\ResourceNotFoundException when a duplicate resource is _not_ found (i.e. should NOT return null)
     */
    abstract public function getDuplicate(\CFX\JsonApi\ResourceInterface $r);

    /**
     * Save a new resource
     *
     * This method is called by `save` after verifications have passed regarding validity and uniqueness
     *
     * @param \CFX\JsonApi\ResourceInterface $r The resource to save
     * @return static
     * @throws \CFX\BadInputException
     */
    abstract protected function saveNew(\CFX\JsonApi\ResourceInterface $r);

    /**
     * Save an existing resource
     * This method is called by `save` after verifications have passed regarding validity and uniqueness
     *
     * @param \CFX\JsonApi\ResourceInterface $r The resource to save
     * @return static
     * @throws \CFX\BadInputException
     */
    abstract protected function saveExisting(\CFX\JsonApi\ResourceInterface $r);

    /**
     * inflateData -- use the provided data to create a Resource or ResourceCollection object
     *
     * @param array $data Should always be in the format of a "table with rows", i.e.,
     *     [
     *         [ "id" => "1234", "type" => "examples", "attributes" => [] ],
     *     ]
     * @param bool $isCollection Whether or not this data represents a collection (determines function output)
     * @return \CFX\JsonApi\ResourceInterface|\CFX\JsonApi\ResourceCollectionInterface
     * @throws \CFX\Persistence\ResourceNotFoundException if !$isCollection and empty($data)
     */
    protected function inflateData(array $data, $isCollection) {
        if (!$isCollection && count($data) == 0) throw new ResourceNotFoundException("Sorry, we couldn't find some of the data you were looking for.");
        if (!$isCollection && count($data) > 1) throw new \CFX\CorruptDataException("Programmer: You've indicated that you're expecting a single resource, but more than one row was returned.");

        foreach($data as $k => $o) {
            $this->currentData = $o;
            $data[$k] = $this->create(null, 'private');
            if ($this->currentData !== null) throw new \RuntimeException("There appears to be leftover data in the cache. You should make sure that all data objects call this database's `getCurrentData` method from within their constructors. (Offending class: `".get_class($data[$k])."`. Did you overwrite the default constructor?)");
        }
        return $isCollection ?
            $this->newCollection($data) :
            $data[0]
        ;
    }

    /**
     * parseDSL -- parse the DSL query that the user sent in
     *
     * @param string $query A query written in a domain-specific query language
     * @return DSLQueryInterface
     */
    protected function parseDSL($query) {
        return GenericDSLQuery::parse($query);
    }


    /**
     * @inheritdoc
     */
    public function setDebug($debug) {
        $this->debug = (bool)$debug;
        return $this;
    }
}

