PHP Datalayer
==========================================================================

*The base persistence and data resource library for the CFX Framework*

This is a library comprising various classes that implement the [JSON-API Specification](http://jsonapi.org/format) in PHP. It is intended to ease the manipulation of data objects that conform to the ideas of the JSON-API format (that is, "data as objects with attributes and relationships"), and also to provide both incoming and outgoing transport negotiation.

In this way, it can be used to parse a received JSON-API document into parts that can be validated and persisted, and also to easily transfer data from persistence to an API client across the network via JSON-API strings.

It is intended to be extended, such that each specific type of Resource may have its own validation rules and business logic, thus creating a fully custom data landscape.

## Philosophy

The overarching philosophy of this library is that anything can be a **resource**, all **resources** come from **datasources**, and all **sources** exists in a context.

## Examples

To load a JSON-API document into program object form,

```php
$doc = new \CFX\JsonApi\Document(json_decode($jsonString, true));
```

This will yield a number of children in accordance with the JSON-API specification: `errors`, `data`, `links`, `jsonapi`, `meta`, and `included`. (Many of these are not yet fully implemented, so mileage will vary.)

Eventually, it would be useful to turn the `Document` object into a more user-friendly object. For now, though, it remains a fairly faithful and static object representation of the JSON that was passed in.

To continue the above example, I might do this:

```php
$doc = new \CFX\JsonApi\Document(json_decode($jsonString, true));

if (count($doc->getErrors()) > 0) throw new \RuntimeException($doc->getErrors()->summarize());

$data = $doc->getData();

if ($data instanceof \CFX\JsonApi\CollectionInterface) {
    foreach($data as $item) {
        // Do something with item
    }
} else {
    $item = $data;
    if ($item->getAttribute('myAttr') == true) {
        // Do something with item
    } else {
        // Do something else with item
    }
}
```

Note that in the above example, instead of using the `getAttribute` method, we might choose to create a derivative object, `ItemResource`, that has a `getMyAttr` method.

## Future

Much more documentation and development still to come....


