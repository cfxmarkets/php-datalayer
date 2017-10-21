<?php
namespace CFX\JsonApi;

/**
 * OffenderExceptionTrait -- a trait that adds the ability to set, add, and retrieve
 * the names of fields that have caused the exception.
 */
trait OffenderExceptionTrait {
    protected $offenders = [];
    public function setOffenders(array $offenders) {
        $this->offenders = $offenders;
        return $this;
    }
    public function addOffender($offender) {
        $this->offenders[] = $offender;
        return $this;
    }
    public function getOffenders() {
        return $this->offenders;
    }
}

class JsonApiException extends \RuntimeException { }

class CollectionConflictingMemberException extends JsonApiException { }
class CollectionUndefinedIndexException extends JsonApiException { }

class DuplicateIdException extends JsonApiException { }

/**
 * An unrecognized class of data has been passed into a constructor
 *
 * This exception allows objects to provide information about what, specifically,
 * the offending condition was. That data can be consumed further upstream to offer
 * more useful error messages in various contexts.
 */
class MalformedDataException extends JsonApiException {
    use OffenderExceptionTrait;

    protected $offendingData;

    public function setOffendingData(array $data) {
        $this->offendingData = $data;
        return $this;
    }

    public function getOffendingData() {
        return $this->offendingData;
    }
}

/**
 * BadInputException
 * Exception specifying that the input data provided is malformed
 */
class BadInputException extends \InvalidArgumentException {
    protected $inputErrors = [];
    public function getInputErrors() { return $this->inputErrors; }
    public function setInputErrors($errors) {
        if (!is_array($errors)) throw new \RuntimeException("Errors passed to `BadInputException::setInputErrors` must be an array of `ErrorInterface` objects.");
        foreach ($errors as $e) {
            if (!($e instanceof ErrorInterface)) throw new \RuntimeException("Errors passed to `BadInputException::setInputErrors` must be an array of `\CFX\JsonApi\ErrorInterface` objects.");
        }
        $this->inputErrors = $errors;
        return $this;
    }
}



/**
 * UninitializedResourceException
 * The requested functionality requires an initialized resource, but this resource has not been initialized yet.
 */
class UninitializedResourceException extends JsonApiException {
    use OffenderExceptionTrait;
}
class UninitializedRelationshipException extends JsonApiException {
    use OffenderExceptionTrait;
}
class UnknownAttributeException extends JsonApiException {
    use OffenderExceptionTrait;
}
class UnknownRelationshipException extends JsonApiException {
    use OffenderExceptionTrait;
}
class UnserializableObjectStateException extends JsonApiException {
    use OffenderExceptionTrait;
}

