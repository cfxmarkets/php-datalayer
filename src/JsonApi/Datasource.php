<?php
namespace CFX\JsonApi;

class Datasource implements DatasourceInterface {
    function newJsonApiDocument($data=null) { return new Document($this, $data); }
    function newJsonApiResource($data=null, $type=null, $validAttrs=null, $validRels=null) { return new GenericResource($this, $data, $validAttrs, $validRels); }
    function convertJsonApiResource(ResourceInterface $src, $conversionType) {
        throw new \RuntimeException("Programmer: Don't know how to convert to type `$conversionType`. Please implement this by overriding the `convertJsonApiResource` method in your datasource.");
    }
    function newJsonApiRelationship($data) { return new Relationship($this, $data); }
    function newJsonApiError($data) { return new Error($this, $data); }
    function newJsonApiResourceCollection($resources=[]) { return new ResourceCollection($resources); }
    function newJsonApiErrorsCollection($errors=[]) { return new ErrorsCollection($errors); }
    function newJsonApiMeta($data=null) { return new Meta($data); }
    function newJsonApiLink($data=null) { return new Link($this, $data); }
    function newJsonApiLinksCollection($links=[]) { return new LinksCollection($links); }
    function getCurrentData() { return null; }
}

