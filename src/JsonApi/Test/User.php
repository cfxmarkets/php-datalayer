<?php
namespace CFX\JsonApi\Test;

class User extends \CFX\JsonApi\AbstractResource {
    use Rel2MTrait;

    protected $resourceType = 'test-users';
    protected $attributes = [ 'name' => null, 'dob' => null ];
    protected $relationships = [ 'friends', 'boss' ];

    public function setName($val) {
        $this->_setAttribute('name', $val);
    }

    public function setDob($val) {
        $this->_setAttribute('dob', $val);
    }

    public function setFriends(\CFX\JsonApi\ResourceCollectionInterface $r=null) {
        $this->_setRelationship('friends', $r);
    }

    public function setBoss(\CFX\JsonApi\ResourceInterface $r=null) {
        $this->_setRelationship('boss', $r);
    }

    public function getName() { return $this->attributes['name']; }
    public function getDob() { return $this->attributes['dob']; }
    public function getFriends() { return $this->relationships['friends']->getData(); }
    public function getBoss() { return $this->relationships['boss']->getData(); }
    public function getFriendsRelationship() { return $this->relationships['friends']; }
    public function getBossRelationship() { return $this->relationships['boss']; }

}


