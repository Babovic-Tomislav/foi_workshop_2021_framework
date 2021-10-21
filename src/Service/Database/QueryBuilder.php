<?php

namespace App\Service\Database;

use App\Helper\Arr;
use App\Model\AbstractModel;
use App\Model\Address;
use App\Model\City;
use App\Model\Employer;
use PDO;

class QueryBuilder
{
    /** @var string */
    private $where;

    /** @var string  */
    private  $select = '*';

    /** @var string  */
    private  $sql;

    /** @var array  */
    private  $with;

    /** @var string  */
    private  $join;

    /** @var AbstractModel  */
    private $model;

    /** @var int */
    private $limit;

    public function __construct(AbstractModel $model)
    {
        $this->model = $model;
    }

    public function get()
    {
        $this->sql = "SELECT $this->select FROM " . $this->model->getTableName() . " ";

        if (isset($this->where)) {
            $this->sql .= $this->where;
        }

        if (isset($this->limit)) {
            $this->sql .= $this->limit;
        }

        if (isset($this->join)) {
            $this->sql .= $this->join;
        }

        $result = $this->model->getPdo()->query($this->sql)->fetchAll(PDO::FETCH_ASSOC);

        return $this->resolveResult($result);
    }

    public function limit(int $limit)
    {
        $this->limit = "LIMIT $limit";
    }

    public function find(int $id)
    {
        $this->where = "WHERE (id = $id)";

        return $this->get()[0];
    }

    public function where(string $field, string $operation, $condition, string $concatenator = 'AND') {
        if (!isset($this->where)) {
            $this->where = "WHERE ($field $operation $condition)";
        } else {
            $this->where .= " $concatenator ($field $operation '$condition')";
        }

        return $this;
    }

    public function orWhere(string $field, string $operation, $condition)
    {
        $this->where($field, $operation, $condition, 'OR');

        return $this;
    }

    public function select(array $columns)
    {
        $this->select = rtrim(implode(', ', $columns), ', ');

        return $this;
    }

    private function resolveResult(array $result)
    {
        if (isset($this->with)) {
            foreach ($this->with as $relation) {
                $resultIds = Arr::get($result, 'id');

                if (str_contains($relation, '.')) {
                    list($relation, $relationRelation) = explode('.', $relation, 2);
                }

                $subQueryBuilder = $this->model->{$relation}();

                $subQueryBuilder->where = str_replace(' )', implode(', ', $resultIds).")", $subQueryBuilder->where);

                if (isset($relationRelation)) {
                    $subQueryBuilder->with($relationRelation);
                    unset($relationRelation);
                }

                $relationObjects[$relation] = $subQueryBuilder->get();
            }
        }

        $objects =[];

        foreach ($result as $row) {
            foreach ($row as $key => $value) {
                if (!in_array($key, $this->model->getFields())) {
                    $pivotIds[$row['id']][$value] = $value;
                    unset($row[$key]);
                }
            }

            $object = $this->model->create($row);

            if (isset($relationObjects)) {
                foreach ($relationObjects as $key => $array) {
                    if (array_key_exists('objects', $array)) {
                        foreach ($array['objects'] as $relationObject) {
                            if (!isset($object->{$key})) {
                                $object->{$key} = [];
                            }

                            if (in_array($object->getId(), $array['pivotIds'][$relationObject->getId()])) {
                                $object->{$key} []= $relationObject;

                                unset($array['pivotIds'][$relationObject->getId()][$object->getId()]);
                            }
                        }
                    } else {
                        foreach ($array as $relationObject) {
                            if (
                                (
                                    isset($object->{$relationObject->getForeignKey()}) &&
                                    $object->{$relationObject->getForeignKey()} == $relationObject->id
                                ) ||
                                (
                                    isset($relationObject->{$this->model->getForeignKey()}) &&
                                    $relationObject->{$this->model->getForeignKey()} == $object->id
                                )
                            ) {
                                if (!isset($object->$key)) {
                                    $object->$key = [];
                                }

                                $object->$key [] = $relationObject;
                            }
                        }
                        if (isset($object->fields[$key])) {
                            unset($object->fields[$key]);
                        }
                    }
                }
            }

            $objects []= $object;
        }

        if (isset($pivotIds)) {
            return [
                'objects' => $objects,
                'pivotIds' => $pivotIds
            ];
        }

        return $objects;
    }

    public function with(string $relation)
    {
        $this->with []= $relation;

        return $this;
    }

    public function join(string $tableName, string $firstTableColumn, string $operation, string $selcondTableColumn)
    {
        $this->join .= "INNER JOIN $tableName ON $firstTableColumn $operation " . $this->model->getTableName() . '.' . $selcondTableColumn;

        return $this;
    }
}

