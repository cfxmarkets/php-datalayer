<?php
namespace CFX\JsonApi;

interface ResourceInterface extends \JsonSerializable, \KS\ErrorHandlerInterface {
    function getResourceType();
    function getId();
    function setId($id);
    function updateFromData(array $data);
    function getChanges();
    function hasChanges();
    function save();
}

