<?php

namespace App\Traits;

trait Relation
{
    public function hasOne(string $relation, string $foreignKey = null, string $localKey = null)
    {
        $class = new $relation;

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $relation = strtolower((new \ReflectionClass($class))->getShortName());

        static::$fields []= $relation;

        $this->$relation = [];

        return $class->where($foreignKey, '=', '( )');
    }

    public function hasMany(string $relation, string $foreignKey = null, string $localKey = null)
    {
        $class = new $relation;

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $relation = strtolower((new \ReflectionClass($class))->getShortName());

        static::$fields []= $relation;

        return $class->where($foreignKey, 'IN', '( )');
    }

    public function belongsTo(string $relation, string $localKey = null)
    {
        $class = new $relation;

        $localKey = $localKey ?: $class->getKeyName();

        $relation = strtolower((new \ReflectionClass($class))->getShortName());

        static::$fields []= $relation;

        return $class->where($localKey, 'IN', '( )');
    }

    public function morphTo(string $relation, string $tableName, string $foreignKey = null, string $localKey = null)
    {
        $class = new $relation;

        $classForeignKey = $class->getForeignKey();
        $classLocalKey = $class->getKeyName();

        $foreignKey = $this->getForeignKey();

        $relation = strtolower((new \ReflectionClass($class))->getShortName());

        static::$fields []= $relation;

        return $class
            ->select(
                [
                    "$class->tableName.*",
                    $tableName . '.' . $foreignKey
                ]
            )
            ->join($tableName, $tableName . '.' . $classForeignKey, '=', $classLocalKey
            );
    }
}