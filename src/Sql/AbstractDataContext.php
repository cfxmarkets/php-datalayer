<?php
namespace CFX\Persistence\Sql;

abstract class AbstractDataContext extends \CFX\Persistence\AbstractDataContext {
    /**
     * @var array A key-indexed array of PDOs or Closures that instantiate PDOs (@see __construct())
     */
    protected $pdos;

    /**
     * Construct a SQL Data Context
     *
     * @param array $pdos An array of named PDOs (or Closures that instantiate PDOs). For example:
     *      [
     *          'default' => function() {
     *              $pdo = new \PDO('mysql:unix_socket=/var/run/mysql/mysql.sock;dbname=exampledb', 'dev', 'dev');
     *              $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
     *              return $pdo;
     *          } 
     *      ]
     */
    public function __construct(array $pdos) {
        $this->pdos = $pdos;
    }

    /**
     * Execute the given SQL Query
     *
     * @param QueryInterface $query
     * @return array|string|int|null Returns the result set on SELECT queries, or the last insert id (if applicable) on other queries
     */
    public function executeQuery(QueryInterface $query) {
        $this->log(\Psr\Log\LogLevel::DEBUG, $query->constructQuery());
        $this->log(\Psr\Log\LogLevel::DEBUG, var_export($query->params, true));

        $q = $this->getPdo($query->database)->prepare($query->constructQuery());
        $q->execute($query->params);

        if ($q->columnCount() > 0) {
            return $q->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            return $this->getPdo($query->database)->lastInsertId();
        }
    }

    /**
     * Gets an actual PDO (by name) from the PDOs array, instantiating if necessary
     *
     * @param string $name The name of the PDO to retrieve
     * @return \PDO
     * @throws \RuntimeException
     */
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

