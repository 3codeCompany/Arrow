<?php
namespace Arrow\Models;
interface IDataSource{
	public function getFields();
	public function getList($criteria);
	public function countList($criteria);
	public function getModelInstance( $key );
	public function getModel(  );
	public function getListActions();
}
?>