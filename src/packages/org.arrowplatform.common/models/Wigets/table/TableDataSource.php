<?php
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 05.08.13
 * Time: 16:29
 * To change this template use File | Settings | File Templates.
 */
namespace Arrow\Package\Common;

use Arrow\Controls\API\Table\ITableDataSource;
use Arrow\Exception;
use Arrow\ORM\Persistent\Criteria;
use Arrow\ORM\DB;
use Arrow\RequestContext;

class TableDataSource extends Criteria implements ITableDataSource
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

    public function getRows($columns, $debug)
    {

        foreach($columns as $c)
            $this->addColumn($c);
        if ($debug) {
            $result = $this->find();
            //\ADebug::log(DB::getDB()->getLastQuery());

            return $result;
        }
        return $this->find();
    }

    public function addOrder($order, $direction)
    {
        if(isset($this->customSort[$order])){
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

        if(isset($this->customFilters[$field])){
            return $this->customFilters[$field]($this,$field,$value,$type,$filter);
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
                $tmp = explode("-",$value);
                if(count($tmp) != 2 )
                    $tmp = explode(":",$value);

                $this->c($field, $tmp, Criteria::C_BETWEEN);
                break;
            case "<x<in":
                $tmp = explode("-",$value);
                if(count($tmp) != 2 )
                    $tmp = explode(":",$value);
                if(count($tmp) != 2 )
                    $tmp = explode(" do ",$value);

                $this->c($field, $tmp[0], Criteria::C_GREATER_EQUAL);
                if(isset($tmp[1]))
                    $this->c($field, $tmp[1], Criteria::C_LESS_EQUAL);
                break;
            default:
                throw new Exception("Unknow filter type:" .$type);
        }

        return $this;
    }
}