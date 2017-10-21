<?php
namespace CFX\JsonApi;

interface GenericResourceInterface extends ResourceInterface {
    function setAttribute($attr, $val);
    function getAttribute($k);
    function getAttributes();
    function setRelationship(RelationshipInterface $r);
    function getRelationship($k);
    function getRelationships();
}

