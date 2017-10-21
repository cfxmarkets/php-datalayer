<?php
namespace CFX\JsonApi;

interface NamedMemberInterface extends \JsonSerializable {
    function getMemberName();
}

