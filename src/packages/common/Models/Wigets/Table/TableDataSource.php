<?php
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 05.08.13
 * Time: 16:29
 * To change this template use File | Settings | File Templates.
 */

namespace Arrow\Common\Models\Wigets\Table;

use Arrow\Exception;
use Arrow\ORM\DB\DB;
use Arrow\ORM\Persistent\Criteria;
use Arrow\ORM\Persistent\DataSet;


interface ITableDataSource
{
    const FILTER_EQUAL = "==";
    const FILTER_IN = "IN";
    const FILTER_NOT_IN = "NOT IN";
    const FILTER_LIKE = "LIKE";
    const FILTER_NOT_EQUAL = "!=";
    const FILTER_NOT_LIKE = "NOT LIKE";
    const FILTER_START_WITH = "^%";
    const FILTER_END_WITH = "%$";

    public function getRows($columns, $debug);

    public function addOrder($order, $direction);

    public function limit($index, $length);

    public function count();

    public function applyFilterSearch($field, $value, $type, $filter);

    public function dataPrepared($data);

}


class TableDataSource extends Criteria
{

    protected $afterDataPrepared = [];
    protected $globalSearchFields;
    protected $customFilters = [];
    protected $customSort = [];

    /**
     * @param $class
     * @return TableDataSource
     */
    public static function fromClass($class)
    {
        $ds = new TableDataSource($class);
        $ds->setColumns([]);
        return $ds;
    }

    public static function prepareCriteria(Criteria $criteria, $data)
    {
        $criteria->setColumns([]);

        if (isset($data["columns"])) {
            foreach ($data["columns"] as $col) {
                if (isset($col["field"]) && $col["field"]) {
                    $criteria->addColumn($col["field"]);

                } elseif (isset($col["columns"])) {
                    foreach ($col["columns"] as $col) {
                        if ($col["field"]) {
                            $criteria->addColumn($col["field"]);
                        }
                    }
                }

            }
        }

        if (isset($data["order"])) {
            foreach ($data["order"] as $order) {
                $criteria->order($order["field"], $order["dir"]);
            }
        }

        //print_r($data["filters"]);


        if (isset($data["filters"])) {
            foreach ($data["filters"] as $filter) {
                if ($filter["condition"] == "<x<in") {
                    $tmp = explode(" : ", $filter["value"]);
                    self::applyCriteriaFilterSearch($criteria, $filter["field"], $tmp[0], Criteria::C_GREATER_EQUAL, "x");
                    if (isset($tmp[1])) {
                        self::applyCriteriaFilterSearch($criteria, $filter["field"], $tmp[1], Criteria::C_LESS_EQUAL, "x");
                    }

                } else {
                    self::applyCriteriaFilterSearch($criteria, $filter["field"], $filter["value"], $filter["condition"], "x");
                }
            }
        }


        return $criteria;
    }

    public static function prepareResponse(Criteria $criteria, $data, $fetchType = DataSet::AS_ARRAY)
    {
        $onPage = $data["onPage"] ?? 25;
        $currentPage = $data["currentPage"] ?? 1;
        $countAll = $criteria->count();
        $criteria->limit(($currentPage - 1) * $onPage, $onPage);
        $result = $criteria->find();
        $query = $result->getQuery();
        if ($fetchType == DataSet::AS_ARRAY) {
            $result = $result->toArray($fetchType);
        }
        return ["data" => $result, "countAll" => $countAll, "debug" => $query];
    }

    public function setConfData($data)
    {

        return $this;
    }

    public function getRows($columns, $debug)
    {

        foreach ($columns as $c) {
            $this->addColumn($c);
        }
        if ($debug) {
            $result = $this->find();
            \ADebug::log(DB::getDB()->getLastQuery());

            return $result;
        }
        return $this->find();
    }

    public function addOrder($order, $direction)
    {
        if (isset($this->customSort[$order])) {
            return $this->customSort[$order]($this);
        }


        $this->order($order, $direction);
    }

    public function limit($index, $length)
    {
        parent::limit($index, $length);
        return $this;
    }

    public function count()
    {
        return parent::count();
    }

    /**
     * @param array $afterDataPrepared
     */
    public function addAfterDataPrepared(callable $afterDataPrepared)
    {
        $this->afterDataPrepared[] = $afterDataPrepared;
        return $this;
    }


    public function dataPrepared($data)
    {
        foreach ($this->afterDataPrepared as $call) {
            $call($data);
        }
    }

    public function applyGlobalSearch($searched)
    {
        $this->addSearchCondition($this->globalSearchFields, '%' . trim($searched) . '%');
    }

    /**
     * @param array $globalSearchFields
     * @return static
     */
    public function setGlobalSearchFields(array $globalSearchFields)
    {
        $this->globalSearchFields = $globalSearchFields;
        return $this;
    }

    /**
     * @return array
     */
    public function getGlobalSearchFields()
    {
        return $this->globalSearchFields;
    }

    public function addCustomFilter($name, callable $filter)
    {
        $this->customFilters[$name] = $filter;
        return $this;
    }

    public function addCustomSorter($name, callable $filter)
    {
        $this->customSort[$name] = $filter;
        return $this;
    }


    public function applyFilterSearch($field, $value, $type, $filter)
    {

        if (isset($this->customFilters[$field])) {
            return $this->customFilters[$field]($this, $field, $value, $type, $filter);
        }


        switch ($type) {

            case ITableDataSource::FILTER_IN:

                $this->c($field, $value, Criteria::C_IN);
                break;
            case ITableDataSource::FILTER_NOT_IN:
                $this->c($field, $value, Criteria::C_NOT_IN);
                break;
            case ITableDataSource::FILTER_EQUAL:
                $this->c($field, $value);
                break;
            case "=":
                $this->c($field, $value);
                break;
            case ITableDataSource::FILTER_LIKE:
                $this->c($field, "%" . $value . "%", Criteria::C_LIKE);
                break;
            case ITableDataSource::FILTER_NOT_EQUAL:
                $this->c($field, $value, Criteria::C_NOT_EQUAL);
                break;
            case ITableDataSource::FILTER_NOT_LIKE:
                $this->c($field, "%" . $value . "%", Criteria::C_NOT_LIKE);
                break;
            case ITableDataSource::FILTER_START_WITH:
                $this->c($field, $value . "%", Criteria::C_LIKE);
                break;
            case ITableDataSource::FILTER_END_WITH:
                $this->c($field, "%" . $value, Criteria::C_LIKE);
                break;
            case ">":
                $this->c($field, $value, Criteria::C_GREATER_THAN);
                break;
            case ">=":
                $this->c($field, $value, Criteria::C_GREATER_EQUAL);
                break;
            case "<":
                $this->c($field, $value, Criteria::C_LESS_THAN);
                break;
            case "<=":
                $this->c($field, $value, Criteria::C_LESS_EQUAL);
                break;
            case "<x<":
                $tmp = explode("-", $value);
                if (count($tmp) != 2) {
                    $tmp = explode(":", $value);
                }

                $this->c($field, $tmp, Criteria::C_BETWEEN);
                break;
            case "<x<in":
                $tmp = explode("-", $value);
                if (count($tmp) != 2) {
                    $tmp = explode(":", $value);
                }
                if (count($tmp) != 2) {
                    $tmp = explode(" do ", $value);
                }

                $this->c($field, $tmp[0], Criteria::C_GREATER_EQUAL);
                if (isset($tmp[1])) {
                    $this->c($field, $tmp[1], Criteria::C_LESS_EQUAL);
                }
                break;
            default:
                throw new Exception("Unknow filter type:" . $type);
        }

        return $this;
    }

    public static function applyCriteriaFilterSearch(Criteria $criteria, $field, $value, $type, $filter)
    {

        if (isset($criteria->customFilters[$field])) {
            return $criteria->customFilters[$field]($criteria, $field, $value, $type, $filter);
        }


        switch ($type) {

            case ITableDataSource::FILTER_IN:

                $criteria->c($field, $value, Criteria::C_IN);
                break;
            case ITableDataSource::FILTER_NOT_IN:
                $criteria->c($field, $value, Criteria::C_NOT_IN);
                break;
            case ITableDataSource::FILTER_EQUAL:
                $criteria->c($field, $value);
                break;
            case "=":
                $criteria->c($field, $value);
                break;
            case ITableDataSource::FILTER_LIKE:
                $criteria->c($field, "%" . $value . "%", Criteria::C_LIKE);
                break;
            case ITableDataSource::FILTER_NOT_EQUAL:
                $criteria->c($field, $value, Criteria::C_NOT_EQUAL);
                break;
            case ITableDataSource::FILTER_NOT_LIKE:
                $criteria->c($field, "%" . $value . "%", Criteria::C_NOT_LIKE);
                break;
            case ITableDataSource::FILTER_START_WITH:
                $criteria->c($field, $value . "%", Criteria::C_LIKE);
                break;
            case ITableDataSource::FILTER_END_WITH:
                $criteria->c($field, "%" . $value, Criteria::C_LIKE);
                break;
            case ">":
                $criteria->c($field, $value, Criteria::C_GREATER_THAN);
                break;
            case ">=":
                $criteria->c($field, $value, Criteria::C_GREATER_EQUAL);
                break;
            case "<":
                $criteria->c($field, $value, Criteria::C_LESS_THAN);
                break;
            case "<=":
                $criteria->c($field, $value, Criteria::C_LESS_EQUAL);
                break;
            case "<x<":
                $tmp = explode("-", $value);
                if (count($tmp) != 2) {
                    $tmp = explode(":", $value);
                }

                $criteria->c($field, $tmp, Criteria::C_BETWEEN);
                break;
            case "<x<in":
                $tmp = explode("-", $value);
                if (count($tmp) != 2) {
                    $tmp = explode(":", $value);
                }
                if (count($tmp) != 2) {
                    $tmp = explode(" do ", $value);
                }

                $criteria->c($field, $tmp[0], Criteria::C_GREATER_EQUAL);
                if (isset($tmp[1])) {
                    $criteria->c($field, $tmp[1], Criteria::C_LESS_EQUAL);
                }
                break;
            default:
                throw new Exception("Unknow filter type:" . $type);
        }

        return $criteria;
    }
}
