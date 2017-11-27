<?php
namespace CFX\Persistence\Test;

class Person extends \CFX\JsonApi\AbstractResource {
    protected $resourceType = 'test-people';
    protected $attributes = [
        'name' => null,
    ];

    public function setName($name) {
        $this->_setAttribute('name', $name);
        if ($name == 'bad') {
            $this->setError('name', 'bad', new \CFX\JsonApi\Error([
                'status' => 400,
                'title' => "Bad `name`",
                'detail' => "Name can't be 'bad'."
            ]));
        } else {
            $this->clearError('name', 'bad');
        }
        return $this;
    }

    public static function getTestData() {
        return [
            'id' => '1',
            'type' => 'test-people',
            'attributes' => [
                'name' => 'Jim Chavo'
            ],
        ];

    }
}

