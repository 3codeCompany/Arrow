<?php namespace Arrow\Models;
namespace Arrow\Models;
interface ISingleton{

	/**
	 * Returns  instance
	 *
	 * @return \Arrow\Models\ISingleton
	 */
	public static function getDefault();
}
?>