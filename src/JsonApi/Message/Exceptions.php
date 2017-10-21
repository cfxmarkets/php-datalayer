<?php
namespace CFX\JsonApi;

class ProtocolException extends \RuntimeException { }

class BadAcceptException extends ProtocolException { }
class BadContentTypeException extends ProtocolException { }
class BadUriException extends ProtocolException { }
class MissingBodyException extends ProtocolException { }
class InvalidOperationException extends ProtocolException { }

class JsonApiProtocolException extends ProtocolException { }
class JsonApiMissingDataException extends JsonApiProtocolException { }
class JsonApiBadInputException extends JsonApiProtocolException { }

