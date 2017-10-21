<?php
namespace CFX\JsonApi;

trait ServerRequestTrait {
    protected $requestedResourceType;
    protected $requestedResourceId;
    protected $handlesRelationships = false;
    protected $requestedRelationshipName;
    protected $requestedRelationshipId;

    protected static function getEndpointProps() { return ['requestedResourceType','requestedResourceId','handlesRelationships','requestedRelationshipName', 'requestedRelationshipId']; }
    protected static function getPathFormat() { return '/[resource](/[id](/relationships(/[relationshipName](/[relationshipId]))))'; }

    /**
     * Validates the Request's adherence to JSON-API transport protocol
     *
     * @return static
     * @throws BadAcceptException Thrown when the `Accept` header is not present or not `application/vnd.api+json`.
     * @throws BadContentTypeException Thrown on POST, PUT, and PATCH requests when the `Content-Type` header is not present or not equal to `application/vnd.api+json`
     * @throws BadUriException Thrown when the URI is not formatted in a way that's understandable as a JSON-API request
     */
    public function validateProtocol() {
        if (!$this->getHeader('Accept') || !in_array('application/vnd.api+json', $this->getHeader('Accept'))) throw new BadAcceptException("Your request must specify that it accepts content of type `application/vnd.api+json` through the `Accept` header.");
        if (in_array($this->getMethod(), ["POST", "PUT", "PATCH"]) && (!$this->getHeader('Content-Type') || !in_array('application/vnd.api+json', $this->getHeader('Content-Type')))) throw new BadContentTypeException("Your request must specify that it is sending content of type `application/vnd.api+json` through the `Content-Type` header");

        // Must have a resource type
        if (!$this->requestedResourceType) throw new BadUriException("Missing resource type. Arguments passed to this API should conform to the following format: `".static::getPathFormat()."`. You've passed `{$this->getUri()->getPath()}`.");

        // Must specify relationship requests correctly
        if ($this->handlesRelationships && $this->handlesRelationships != 'relationships') throw new BadUriException("Malformed `relationships` keyword. Arguments passed to this API should conform to the following format: `".static::getPathFormat()."`. You've passed `{$this->getUri()->getPath()}`.");
        else $this->handlesRelationships = (bool)$this->handlesRelationships;

        // If the request is a POST/PUT/PATCH...
        if (in_array($this->getMethod(), ['POST','PUT','PATCH'])) {
            // then it must have a jsonapi doc and valid data
            if (!$this->getJsonApiDoc() || !$this->getJsonApiDoc()->getData()) throw new JsonApiMissingDataException("It appears as though you're trying to create or update a resource of type `{$this->getRequestedResourceType()}`, but you haven't passed in any data. Please pass in a resource in json-api format via the request body.");

            // and if it's a POST, it can't have an ID
            if ($this->getMethod() == 'POST') {
                if ($this->requestedResourceId) throw new JsonApiBadInputException("It appears as though you're trying to POST to a specific asset (id `$this->requestedResourceId`). You may only POST to a resource collection endpoint (e.g., `POST /my-resources`, not `POST /my-resources/12345`). If you'd like to update this resource, you should use `PATCH` instead.");
                if (($id = $this->getJsonApiDoc()->getData()->getId())) throw new JsonApiBadInputException("It appears as though you've sent an existing resource (id `$id`) with a POST request. POST requests are for creating new resources. If you'd like to udpate a resource, you should use PATCH or PUT instead. If you'd like to create a new resource, send the resource without an ID.");
            }
        }

        return $this;
    }



    /**
     * Parses the path into components after object construction
     *
     * @param array $path An array representing the path parts to operate on (could be reduced by derivative objects)
     * @return array $path The remaining path components to operate on further down the inheritance chain
     * @throws ProtocolException Thrown when there are extra, unrecognized path parameters
     */
    protected function parsePath(array $path) {
        $endpointProps = static::getEndpointProps();
        for ($i = 0; $i < count($endpointProps) && count($path) > 0; $i++) $this->{$endpointProps[$i]} = array_shift($path);

        // Must not have extra params
        if (count($path) > 0) throw new ProtocolException("Extra path arguments. Arguments passed to this API should conform to the following format: `".static::getPathFormat()."`. You've passed `{$this->getUri()->getPath()}`.");

        return $path;
    }


    /**
     * Parse body into JsonApi document (if applicable) and return new instance
     *
     * @param FactoryInterface $f A factory with which to instantiate JsonApi objects
     * @return static
     * @throws \InvalidArgumentException Thrown when invalid arguments are passed to Document.
     * @throws UnknownResourceTypeException Thrown when Factory doesn't know how to instantiate the requested type of resource
     * @throws \RuntimeException Thrown when a JsonApi Resource is passed an attribute with a non-string index
     * @throws DuplicateIdException Thrown on attempts to set the ID of a resource that already has an ID
     * @throws UnknownRelationshipException Thrown when a Resource is passed a non-approved relationship
     * @throws CollectionConflictingMemberException
     * @throws UnserializableObjectStateException
     */
    public function parseBody(FactoryInterface $f) {
        if (!in_array($this->getMethod(), ["POST", "PUT", "PATCH"])) return $this;
        $new = $this->withParsedBody(json_decode((string)$this->getBody(), true));
        $doc = $f->newJsonApiDocument($new->getParsedBody());
        return $new->withJsonApiDoc($doc);
    }



    public function getRequestedResourceType() { return $this->requestedResourceType; }
    public function getRequestedResourceId() { return $this->requestedResourceId; }
    public function getRequestedRelationshipName() { return $this->requestedRelationshipName; }
    public function getRequestedRelationshipId() { return $this->requestedRelationshipId; }
    public function getEndpointName() {
        $path = [];
        foreach(static::getEndpointProps() as $p) {
            if (!$this->$p) break;
            if ($p == 'handlesRelationships') $p = 'relationships';
            $path[] = $p;
        }
        return $this->getMethod()." /".str_replace('_', '-', implode("/", $path));
    }

    public function isForResourceCollection() { return $this->requestedResourceId == null; }
    public function isForPrimaryResource() { return $this->requestedResourceId !== null && $this->handlesRelationships == false; }
    public function isForRelationshipsCollection() { return $this->handlesRelationships && !$this->requestedRelationshipName; }
    public function isForSpecificRelationship() { return $this->requestedRelationshipName !== null; }
    public function isForSpecificRelationshipMember() { return $this->requestedRelationshipId !== null; }
}

