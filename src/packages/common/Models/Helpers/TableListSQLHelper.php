<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 27.08.2017
 * Time: 11:06
 */

namespace Arrow\Common\Models\Helpers;

use Arrow\Common\Models\Wigets\Table\TableDataSource;
use Arrow\Media\Models\MediaAPI;
use Arrow\ORM\Persistent\Criteria;
use Arrow\ORM\Persistent\DataSet;

class TableListSQLHelper
{

    private $defaultOrder = "";
    private $filters = [];
    private $sorters = [];
    private $debug = false;
    private $inputData = null;

    private $db;

    public function __construct(\PDO $db, $inputData = false)
    {
        $this->db = $db;
        if (!$inputData) {
            $this->inputData = json_decode(file_get_contents('php://input'), true);
        } else {
            $this->inputData = $inputData;
        }
    }


    public function getInputData()
    {
        return $this->inputData;
    }


    public function getListData($query)
    {

        $data = $this->inputData;

        if (empty($data["order"]) && !empty($this->defaultOrder)) {
            $query = str_replace("{order}", $this->defaultOrder, $query);
        }

        if (!empty($data["order"])) {
            $tmp = "";
            foreach ($data["order"] as $field => $el) {
                $tmp .= ", {$field} {$el["dir"]}";
            }
            $query = str_replace("{order}", trim($tmp, ","), $query);
        }

        return [
            "data" => $this->db->query($query)->fetchAll(\PDO::FETCH_ASSOC),
            "countAll" => 1,
            "debug" => $query
        ];


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


        $response = TableDataSource::prepareResponse(
            $criteria,
            $data,
            ($this->withMedia || $this->objectsPostProcess) ? DataSet::AS_OBJECT : $this->fetchType
        );

        if ($this->withMedia) {
            MediaAPI::prepareMedia($response["data"]);
        }

        if ($this->objectsPostProcess) {
            ($this->objectsPostProcess)($response["data"]);
        }

        if (($this->withMedia || $this->objectsPostProcess) && DataSet::AS_OBJECT != $this->fetchType) {
            $response["data"]->toArray();
        }

        if ($this->fetchType == DataSet::AS_ARRAY && $this->arrayPostProcess) {
            ($this->arrayPostProcess)($response["data"]);
        }

        if ($this->debug === true) {
            $response["debug"] = $response["debug"];
        } elseif ($this->debug) {
            $response["debug"] = $this->debug;
        } else {
            $response["debug"] = false;
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

    public function addDefaultOrder($order)
    {
        $this->defaultOrder = $order;
        return $this;
    }


    public function setDebug($debug)
    {
        $this->debug = $debug;
        return $this;
    }


}
