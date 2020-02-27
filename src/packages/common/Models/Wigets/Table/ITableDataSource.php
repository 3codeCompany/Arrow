<?php
namespace Arrow\Common\Models\Wigets\Table;

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

