<?php
namespace CFX\JsonApi;

trait ValidationQueueTrait {
    private $validationQueue = [];

    protected function queueAttributeValidation($key, $waitingFor) { $this->queueValidation('attributes', 'Attribute', $key, $waitingFor); }
    protected function queueRelationshipValidation($key, $waitingFor) { $this->queueValidation('relationships', 'Relationship', $key, $waitingFor); }
    protected function queueValidation($type, $entity, $key, $waitingFor) {
        if (!array_key_exists($waitingFor, $this->validationQueue)) $this->validationQueue[$waitingFor] = [];
        if (!array_key_exists($type, $this->validationQueue[$waitingFor])) $this->validationQueue[$waitingFor][$type] = [];
        if (!in_array($key, $this->validationQueue[$waitingFor][$type])) $this->validationQueue[$waitingFor][$type][] = $key;
        $this->setError($key, "queued", $this->f->newJsonApiError([ "status" => 400, "title" => "Unverifiable $entity `$key`", "detail" => "There is currently no $waitingFor, so we can't validate $entity '$key'." ]));
    }
    protected function releaseValidationQueue($waitingFor) {
        if (!array_key_exists($waitingFor, $this->validationQueue)) $this->validationQueue[$waitingFor] = [];
        if (count($this->validationQueue[$waitingFor]) > 0) {
            foreach($this->validationQueue[$waitingFor] as $which => $queue) {
                foreach($queue as $k => $item) {
                    $this->clearError($item, "queued");
                    $setEntity = "set".ucfirst($item);
                    $getEntity = "get".ucfirst($item);
                    $this->$setEntity($this->$getEntity());
                }
                $this->validationQueue[$waitingFor][$which] = [];
            }
        }
    }
}

