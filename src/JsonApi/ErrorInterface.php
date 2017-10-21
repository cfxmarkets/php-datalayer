<?php
namespace CFX\JsonApi;

interface ErrorInterface extends \KS\ErrorInterface, \JsonSerializable {
    function getId();
    function getLinks();
    function getCode();
    function getSource();
    function getMeta();
}

