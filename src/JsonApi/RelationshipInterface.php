<?php
namespace CFX\JsonApi;

interface RelationshipInterface extends NamedMemberInterface {
    function getName();
    function getLinks();
    function getMeta();
    function getData();
}

