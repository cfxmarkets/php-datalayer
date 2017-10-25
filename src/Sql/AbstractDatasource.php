<?php
namespace CFX\Sql;

/**
 * An abstract SQL Datasource
 *
 * This class is meant to represent a single object. For example, in a `PetStore` data context, you might have `IguanaDatasource`,
 * `CatDatasource`, `DogDatasource`, etc. These individual datasources extends from this abstract source, and all contain references
 * to their parent `PetStore` context.
 */
abstract class AbstractDatasource extends \CFX\AbstractDatasource implements DatasourceInterface {
    public function delete($r) {
        if (!is_string($r) && !is_int($r) && (!is_object($r) || !($r instanceof \CFX\JsonAapi\ResourceInterface))) throw new \InvalidArgumentException("You must pass either a string ID or a Resource into this function.");
        if (is_object($r)) $r = $r->getId();

        if ($r === null) throw new \CFX\UnidentifiedResourceException("Can't delete resource because the resource you're trying to delete doesn't have an ID.");

        $this->executeQuery($this->newSqlQuery([
            'query' => "DELETE FROM `{$r->getResourceType()}`",
            'where' => '`id` = ?',
            'params' => [$r],
        ]));

        return $this;
    }

    protected function saveExisting(\CFX\JsonApi\ResourceInterface $r) {
        $data = $r->getChanges();
        $q = $this->newSqlQuery([
            'query' => "UPDATE `{$r->getResourceType()}` SET `".implode("` = ?, `", array_keys($data['attributes']))."` = ?",
            'where' => '`id` = ?',
            'params' => array_merge(array_values($data['attributes']), $r->getId()),
        ]);

        $this->executeQuery($q);
    }

    protected function saveNew(\CFX\JsonApi\ResourceInterface $r) {
        $r->setId(md5(uniqid()));
        $data = $r->jsonSerialize();
        $placeholders = ['?'];
        for($i = 0; $i < count($data['attributes']); $i++) $placeholders[] = '?';
        $q = $this->newSqlQuery([ "query" => "INSERT INTO `{$r->getResourceType()}` (`id`, `".implode('`, `', array_keys($data['attributes']))."`) VALUES (".implode(", ", $placeholders).")" ]);

        $this->executeQuery($q);
    }

    protected function newSqlQuery(array $q=[]) {
        return new Query($q);
    }
}

