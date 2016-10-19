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
use Arrow\ORM\Criteria;
use Arrow\ORM\DB;
use Arrow\RequestContext;

class ArrayDataSource extends Criteria implements ITableDataSource
{

    protected $afterDataPrepared = [];
    protected $globalSearchFields;
    protected $customFilters = [];
    protected $customSort = [];

    protected $array = [];

    /**
     * @param $class
     * @return TableDataSource
     */
    public static function create(array  $array)
    {
        $ds = new ArrayDataSource($array);

        return $ds;
    }

    public function __construct(array  $array){
        $this->array  = $array;

    }

    public function getRows($columns, $debug)
    {

       return  $this->array;
    }

    public function addOrder($order, $direction )
    {

        //todo sprawdzić dlaczego puste indexy sie laduja ( nule)
        if(!$order)
            return;

        $tmp = [];
        foreach ($this->array as $key => $row) {
            $tmp[$key] = $row[$order];
        }


        array_multisort($tmp, $direction=="asc"?SORT_ASC:SORT_DESC, $this->array);
    }

    public function limit($index, $length)
    {
        parent::limit($index, $length);
        $this->array = array_slice($this->array,$index, $length);
        return $this;
    }

    public function count()
    {
        return count($this->array);
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

                throw new Exception("To implement");
                break;
            case ITableDataSource::FILTER_NOT_IN:
                throw new Exception("To implement");
                break;
            case ITableDataSource::FILTER_EQUAL:
                $comparator = function($row) use( $field, $value){ return $row[$field]==$value; };
                break;
            case ITableDataSource::FILTER_LIKE:
                $this->c($field, "%" . $value . "%", Criteria::C_LIKE);
                $comparator = function($row) use( $field, $value){ return strpos($row[$field],$value)!== false; };
                break;
            case ITableDataSource::FILTER_NOT_EQUAL:
                $comparator = function($row) use( $field, $value){ return $row[$field]!=$value; };
                break;
            case ITableDataSource::FILTER_NOT_LIKE:
                $comparator = function($row) use( $field, $value){ return strpos($row[$field],$value)=== false; };
                break;
            case ITableDataSource::FILTER_START_WITH:
                $comparator = function($row) use( $field, $value){ return strpos($row[$field],$value) === 0; };
                break;
            case ITableDataSource::FILTER_END_WITH:
                $comparator = function($row) use( $field, $value){   $length = strlen($value);    return (substr($row[$field], -$length) === $value); };
                break;
            case ">":
                $comparator = function($row) use( $field, $value){ return $row[$field]>$value; };
                break;
            case ">=":
                $comparator = function($row) use( $field, $value){ return $row[$field]>=$value; };
                break;
            case "<":
                $comparator = function($row) use( $field, $value){ return $row[$field]<$value; };
                break;
            case "<=":
                $comparator = function($row) use( $field, $value){ return $row[$field]<=$value; };
                break;
            case "<x<":
                throw new Exception("To implement");
                break;
            case "<x<in":
                throw new Exception("To implement");
                break;
            default:
                throw new Exception("Unknow filter type");
        }

        $this->array  = array_filter($this->array,$comparator);

        return $this;
    }
}