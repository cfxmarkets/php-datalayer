<?php
namespace CFX\Persistence\Sql;

abstract class AbstractDataContext extends \CFX\Persistence\AbstractDataContext {
    protected $pdos;

    /**
     * Construct a SQL Data Context
     *
     * @param array $pdos An array of named PDOs (or Closures that instantiate PDOs)
     */
    public function __construct(array $pdos) {
        $this->pdos = $pdos;
    }

    public function executeQuery(QueryInterface $query) {
        $q = $this->getPdo($query->database)->prepare($query->constructQuery());
        $q->execute($query->params);

        return $q->fetchAll(\PDO::FETCH_ASSOC);
    }

    protected function getPdo($name='default') {
        if (!array_key_exists($name, $this->pdos)) {
            throw new \RuntimeException("Programmer: Can't find a PDO with the specified name `$name`");
        }

        if ($this->pdos[$name] instanceof \Closure) {
            $pdo = $this->pdos[$name];
            $this->pdos[$name] = $pdo($this);
        }
        if ($this->pdos[$name] instanceof \PDO || $this->pdos[$name] instanceof \CFX\Test\PDO) {
            return $this->pdos[$name];
        }

        $type = is_object($this->pdos[$name]) ? get_class($this->pdos[$name]) : gettype($this->pdos[$name])." (".$this->pdos[$name].")";
        throw new \RuntimeException("Programmer: Values in your pdos array must be either Closures that return valid PDOs or already-instantiated PDOs. You've passed an object which is neither (`$type`).");
    }

}

