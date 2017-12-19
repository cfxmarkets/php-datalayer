PHP Persistence Package
==========================================================================

*This package provides the base persistence layer for the CFX Model Framework.*

This is the second of two essential packages that comprise the CFX data system. The first is [`cfxmarkets/php-jsonapi-objects`](https://github.com/cfxmarkets/php-jsonapi-objects), and if you haven't read about that one yet, you should do so now. This package builds on the concepts introduced in that package.

The overarching philosophy of the CFX data system is that when modeling the real world in a computer system, we deal primarily with **resource** objects stored in **datasources**, and that all resources exist within a **data context.**

While the `php-jsonapi-objects` package provides implementation logic for resource objects and a definition for datasource objects, this package actually implements the foundation for both SQL and REST datasource objects and introduces the concept of the `DataContext`.


## Installation

This library can be installed using the standard composer process:

```bash
composer require cfxmarkets/php-persistence
```


## Usage

Because this is meant to be a foundational library, it only provides (with few exceptions) abstract classes. Furthermore, because it provides the foundation for several different types of persistence (REST and SQL, at the time of this writing), explaining it can be a little complicated. Let's start with a bird's-eye view.


### 30,000 feet

There are only four main classes of object that you'll be dealing with in this package:

* `DataContext`
* `Datasource`
* `DSLQuery`
* `SQLQuery`


> 
> **A Note About Queries**
> 
> CFX has decided to use DSL's to facilitate querying. This allows us to be as vague or specific as we need to be without opening up the security holes introduced by full query languages.
> 
> This is why the `get` function defined in `\CFX\JsonApi\DatasourceInterface` requires only a query string. Depending on implementation, that query string may be interpreted as generally or specifically as desired. In our case, we've chosen to interpret it as a DSL query string, which we parse (by default) into a DSLQuery object in the abstract classes in this package.
> 
> The `GenericDSLQuery` comes equipped only to deal with standard id queries: `id=abcde12345`. It will throw a `BadQueryException` on any deviation from this format. However, the `parseDSL` method of each datasource may return any more specific implementation that returns a DSLQueryInterface. Thus, for datasources that need added query functionality, it can be added through extension of the GenericDSLQuery class and overriding of the `AbstractDatasource::parseDSL` method.
> 
> Regarding SQL Queries, we kind of screwed up here. Really a SQL Query is just a regular DSL Query with added functionality that allows us to use it with a PDO query process. However, our implementation doesn't reflect that, instead defining DSL Queries and SQL Queries as two completely separate things. This is something that is slated for development ASAP, as it could certainly help to streamline the query element of the package in general....
> 
> For now, though, just know that it's an issue on the radar, and that SQL Queries and DSL Queries intersect in an awkward way for the time being.
>



### 10,000 feet

Getting in a little closer, let's talk about how you'll usually use these classes.

First off, the query classes are internal. Those are only used inside datasources, since that's really the only context in which they make sense.

Now, in this package's implementation of DataContexts, you can get datasources by calling properties on a DataContext, rather than calling getter methods. (This was a usability decision and breaks with our normal convention of not using "magic" getters). In the examples, you'll see the main value of DataContexts is to simply coordinate communication between various resource objects and their Datasources. Here's a very simple example of what this might look like:

```php
$brokerage = new \CFX\Brokerage\DataContext($pdos);

$user = $brokerage->users->get("id=$_SESSION[userId]");
$user
    ->updateFromData($_POST['userData'])
    ->save();

$orders = $user->getOrders();

$outstandingOrders = $cfx->orders->newCollection();
foreach($orders as $order) {
    if (!$order->isComplete()) {
        $outstandingOrders[] = $order;
    }
}

echo json_encode(['data' => $outstandingOrders ]);
```

Note that this example could be either a REST context or a SQL context -- they're used in the exact same way. In this case, though, let's move foward assuming we're using a SQL context since that will be more common and will provide a better overview of functionality.




### 2,000 feet

In the above example, we fired up the Brokerage data context, got the user that was currently logged into the session, updated that user's info from a form they posted, then returned a collection of the user's outstanding orders. (Obviously this is a totally contrived example, as there's absolutely no reason to do this.)

You can see that we're able to get the user with a string query that simply requested the user's id, then we just dumped the posted `userData` array into the `updateFromData` method and tried to save it. After that, we got all the user's orders, then iterated through them and aggregated the ones that are currently incomplete.

There are a lot of details in this. Here's the same example, but with some comments to clarify what's going on:

```php
$brokerage = new \CFX\Brokerage\DataContext($pdos);

// Remember, query strings are parsed by default by the `GenericDSLQuery` class, so you can make sure to properly sanitize values
// in that class and derivatives
$user = $brokerage->users->get("id=$_SESSION[userId]");

// AbstractDatasource first checks for input errors, then checks for uniqueness before proceeding to save, so if there are
// problems, this will throw exceptions, which we can catch at an application level
$user
    ->updateFromData($_POST['userData'])
    ->save();

// Now we're getting related data. The `getOrders` method will call up to the Datasource to get all related orders, and the
// Datasource will delegate this to its DataContext. This call is equivalent to saying, `$orders = $brokerage->orders->get("userId={$user->getId()}")`
$orders = $user->getOrders();

// We use the orders datasource to instantiate a new orders collection (usually just a generic \CFX\JsonApi\ResourceCollection, but overridable per datasource)
$outstandingOrders = $cfx->orders->newCollection();
foreach($orders as $order) {
    if (!$order->isComplete()) {
        $outstandingOrders[] = $order;
    }
}

// Finally, we output using json_encode, which automatically serializes each order object to JSON API format
echo json_encode(['data' => $outstandingOrders ]);
```


### 500 feet

Zooming in even further, let's take a brief look at what these objects are actually doing. Really, most of the work is done by the Resource objects, which are outside the scope of this discussion (see [php-jsonapi-objects](https://github.com/cfxmarkets/php-jsonapi-objects) for those). The rest of the work is mostly done by Datasource objects.

In this case, there are two: `UsersDatasource` and `OrdersDatasource`. In the first operation, we're getting an instance of `UsersDatasource` from the brokerage datacontext using the standard `users` property accessor. **In general, you can always access Datasource instances from contexts by using the camelCase representation of their JSON API resource type specification.** We then call the `UsersDatasource::get` method with our query string to get the correct user.

While the `get` method isn't defined in `AbstractDatasource`, it's common for it to follow the following algorithm:

First, it parses the query string using its `parseDSL` method. If it hasn't overridden this method, then all this does is pass the string on to the `GenericDSLQuery::parse` method, which returns a `GenericDSLQuery` object. This step serves to validate and sanitize the query string, so if any invalid characters are passed in, the DSLQuery class that parses the string should throw a `BadQueryException`.

Next, it uses `mapAttribute` and `mapRelationship` together with its defined `fieldMap` property to create a list of database columns to get. It combines this with a call to `getAddress` to create a SELECT string that it passes as the `query` field to `newSqlQuery` (a factory method that just passes parameters on to `new \CFX\Persistence\Sql\Query`).

It completes the SQL Query by using the DSL query's `getWhere` and `getParams` methods to fill in the remaining required data fields, then executes that query.

When that returns, it might do some preprocessing on the raw result (depending on how conformant or non-conformant it is), then it sends the result to `convertToJsonApi` where it is (predictably) converted to JSON API format, then to `inflateData` where it is converted to resource objects. (Because of a design flaw related to the encapsulation of query logic, `ResourceNotFoundException`s are thrown from the `convertToJsonApi` method when applicable. This will likely change when the idea of Queries is addressed.)

The next operation of note is the `save` operation. While this is called on a Resource object, it is actually delegated to the Datasource object. The datasource first checks the resource object for errors (throwing a `BadInputException`, if applicable). Then it checks to see if the object has an ID. If not, it's considered a new object, and the method checks to see if it's a duplicate (throwing a `DuplicateResourceException` if applicable), then delegates it to the `saveNew` method. If it does have an id, then it delegates it to the `saveExisting` method.

Because of the use of exceptions, you don't actually have to check to see if the save was successful. You can assume that if no exceptions were thrown, the save succeeded.

The last important operation to highlight is the `getOrders` method. While this is a resource-specific method, it utilizes the datasource to get the related orders. (And as noted in [this issue](https://github.com/cfxmarkets/php-persistence/issues/8), the datasource's mechansim for doing that is quite awkward at the time of this writing.)

> 
> **Note:** To-many relationships are actually not well handled generally by the system right now. There are some relatively easy fixes for this, but they're not yet on the high-priority list.
> 

What's important to notice about this is how the Resource object delegates to the Datasource object, which further delegates to the DataContext object to get related orders. The operation is actually fairly simple: The resource object tells the datasource, "I'd like to get all Order objects related to me". The Datasource translates this call into a query using the resource's type and id to write the DSL query string, then uses the type to (attempt to) get the OrdersDatasource from the attached datacontext and execute the query against it. The end result is the same as calling `$brokerage->orders->get("userId={$user->getId()}")`, except its handled behind the scenes to make the whole process more programmer-friendly.



## Conclusion

And that's about it! This is not a huge library, so feel free to dig deeper into the source code to learn more about implementation details and possibilities.



## Known Issues and The Future

Here are a few known issues that we still have to address:

* The aformentioned problems with SQL Queries and DSLQueries
* Implementing more complex and powerful DSL query parsing
* Narrowing the role of DataContexts (currently, they handle the actual querying for datasources, which doesn't actually make much sense)


## Note About API Documentation

We're hoping to launch a site (https://developers.cfxtrading.com) soon that will allow us to provide more comprehensive API documentation and other resources for developers. While this site is not live yet, you can still generate good API documentation for this library by cloning the library, installing [Sami](https://github.com/FriendsOfPHP/Sami), and running `sami.phar update sami.config.php`.

