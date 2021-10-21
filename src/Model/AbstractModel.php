<?php

namespace App\Model;

use App\Exception\CustomSqlException;
use App\Exception\UndefinedPropertyException;
use App\Service\Database\Connection;
use App\Service\Database\QueryBuilder;
use App\Traits\Relation;
use BadMethodCallException;
use Error;
use PDO;

class AbstractModel
{
    use Relation;

    /** @var PDO */
    protected $pdo;

    /** @var array  */
    protected static $mandatoryFields;

    /** @var string  */
    protected static $tableName;

    /** @var array  */
    protected static $fillable = [];

    /** @var array  */
    protected static $guard = [];

    /** @var array  */
    protected static $fields = [];

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    public function __construct()
    {
        $this->pdo = Connection::get()->getConnection();

        $this->prepareFields();
    }

    public static function create(array $data = [])
    {
        $object = new static;

        foreach ($data as $key => $value) {
            $object->$key = $value;
        }
        return $object  ;
    }

    public function save(array $data = [])
    {
        if ($this->getId()) {
            $this->update($data);
        } else {
            $this->insert($data);
        }

        return $this;
    }

    /**
     * @param  array  $data
     * @throws CustomSqlException
     *
     * implode spoji array u string bla bla
     * array_key vrati kljuceve
     * rtrim makne desno
     */
    public static function insert(array $data)
    {
        static::checkMandatoryFields($data);

        $sql = 'INSERT INTO ' . static::$tableName . ' (' . implode(', ', array_keys($data)) . ') VALUES (';

        $prepareStatementData = [];
        foreach ($data as $key => $value) {
            $sql .= ":$key, ";
            $prepareStatementData[":$key"] = $value;
        }

        $sql = rtrim($sql, ', ');
        $sql .= ');';

        $stmt = static::getPdo()->prepare($sql);

        $stmt->execute($prepareStatementData);
    }

    public static function update(array $data, array $where = [])
    {
        $sql = 'UPDATE ' . static::$tableName . ' SET ';

        $prepareStatementData = [];
        foreach ($data as $key => $value) {
            $sql .= "$key = :$key, ";
            $prepareStatementData[":$key"] = $value;
        }

        $sql = rtrim($sql, ', ');

        $sql .= ' WHERE ';

        if ($where != []) {
            foreach ($where as $key => $value) {
                $sql .= "$key = :where_$key, ";
                $prepareStatementData[":where_$key"] = $value;
            }
        } else {
            $sql .= 'id = ' . static::getId();
        }

        $sql = rtrim($sql, ', ');

        $stmt = static::getPdo()->prepare($sql);

        $stmt->execute($prepareStatementData);
    }

    public static function delete(array $where) {
        $sql = 'DELETE FROM ' . static::$tableName . ' WHERE ';

        $prepareStatementData = [];
        if ($where != []) {
            foreach ($where as $key => $value) {
                $sql .= "$key = :where_$key, ";
                $prepareStatementData[":where_$key"] = $value;
            }
        } else {
            $sql .= 'id = ' . static::getId();
        }

        $sql = rtrim($sql, ', ');

        $stmt = static::getPdo()->prepare($sql);

        $stmt->execute($prepareStatementData);
    }

    private function checkMandatoryFields(array $data)
    {
        if ($diff = array_diff(array_values(static::$mandatoryFields), array_keys($data))) {
            throw new CustomSqlException($diff);
        }
    }

    public function __get(string $name)
    {
        if (property_exists($this, $name) && isset($this->$name)) {
            return $this->$name;
        }

        return static::$$name;
    }

    public static function __callStatic(string $name, array $arguments)
    {
        $self = new static();

        if (method_exists($self, $name)) {
            return call_user_func_array(array($self, $name), $arguments);
        } else if (property_exists($self, $name = lcfirst(ltrim($name, 'get')))) {
            return $self->$name;
        }

        return $self->$name(...$arguments);
    }

    public function __call(string $name, array $arguments)
    {
        if (method_exists($this, $name)) {
            return call_user_func_array(array($this, $name), $arguments);
        } else if (property_exists($this, $name = lcfirst(ltrim($name, 'get')))) {
            return $this->$name;
        }

        return $this->forwardCallTo($this->newQueryBuilder(), $name, $arguments);
    }

    private function newQueryBuilder()
    {
        return new QueryBuilder($this);
    }

    private function forwardCallTo($object, string $name, array $arguments)
    {
        try {
            return $object->{$name}(...$arguments);
        } catch (Error|BadMethodCallException $e) {
            $pattern = '~^Call to undefined method (?P<class>[^:]+)::(?P<method>[^\(]+)\(\)$~';

            if (! preg_match($pattern, $e->getMessage(), $matches)) {
                throw $e;
            }

            if ($matches['class'] != get_class($object) ||
                $matches['method'] != $name) {
                throw $e;
            }

            throw new BadMethodCallException(sprintf(
                'Call to undefined method %s::%s()', static::class, $name
            ));
        }
    }

    private function prepareFields()
    {
        $sql = "SELECT `COLUMN_NAME` 
                FROM `INFORMATION_SCHEMA`.`COLUMNS` 
                WHERE `TABLE_SCHEMA`='" . $_ENV['DB_NAME'] .
                "' AND `TABLE_NAME`='$this->tableName';";

        $result = $this->pdo->query($sql)->fetchAll();

        foreach ($result as $field ) {
            $field = $field['COLUMN_NAME'];

            static::$fields []= $field;
            $this->$field = '';
        }
    }

    public function __set($name, $value)
    {
        if (in_array($name, static::$fields) || property_exists($this, $name)) {
            $this->$name = $value;
        } else {
            throw new UndefinedPropertyException($name);
        }
    }

    private function getForeignKey()
    {
        return strtolower((new \ReflectionClass($this))->getShortName()) . '_' . $this->primaryKey;
    }

    private function getKeyName()
    {
        return $this->primaryKey;
    }
}