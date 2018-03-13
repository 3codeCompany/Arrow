<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 27.08.2017
 * Time: 11:06
 */

namespace Arrow\Common\Models\Helpers;

use Arrow\Common\Models\Wigets\Table\TableDataSource;
use Arrow\ORM\Persistent\Criteria;
use Arrow\ORM\Persistent\DataSet;

class TableListORMHelper
{

    private $defaultOrder = [];
    private $filters = [];
    private $sorters = [];
    private $debug = false;
    private $fetchType = DataSet::AS_ARRAY;

    private $inputData = null;

    public function __construct()
    {
        $this->inputData = json_decode(file_get_contents('php://input'), true);
    }


    public function getInputData()
    {
        return $this->inputData;
    }

    public function getListData(Criteria $criteria = null)
    {

        $data = $this->inputData;

        foreach ($this->filters as $name => $filter) {
            if (isset($data["filters"][$name])) {
                $filter($criteria, $data["filters"][$name]);
                unset($data["filters"][$name]);
            }
        }
        foreach ($this->sorters as $name => $sorter) {
            if (isset($data["order"][$name])) {
                $sorter($criteria, $data["order"][$name]);
                unset($data["order"][$name]);
            }
        }


        $criteria = TableDataSource::prepareCriteria($criteria, $data);

        if (empty($data["order"]) && !empty($this->defaultOrder)) {
            foreach ($this->defaultOrder as $column) {
                $criteria->order($column[0], $column[1]);
            }
        }

        $response = TableDataSource::prepareResponse($criteria, $data, $this->fetchType);

        if($this->debug === true) {
            $response["debug"] = $response["debug"];
        }elseif($this->debug){
            $response["debug"] = $this->debug;
        }
        return $response;
    }

    public function addFilter($name, callable $fn)
    {
        $this->filters[$name] = $fn;
        return $this;
    }

    public function addSorter($name, callable $fn)
    {
        $this->sorters[$name] = $fn;
        return $this;
    }

    public function addDefaultOrder($column, $dir = Criteria::O_ASC)
    {
        $this->defaultOrder[] = [$column, $dir];
        return $this;
    }

    public function setDebug($debug)
    {
        $this->debug = $debug;
        return $this;
    }

    public function setFetchType($type)
    {
        $this->fetchType = $type;
    }


}
