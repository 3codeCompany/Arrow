<?php


class BitHelper {
	
	public static function add( $value, $bit ) {
		return $value = (int) $value | (int)$bit ;
	}
	
	public static function remove( $value, $bit ) {
		if( (int)((int) $value & (int) $bit ) > 0 ) {
			return $value = (int) $value - (int) $bit ;
		}
		else return (int) $value;
	}
	
	public static function band( $one, $two ) {
		return (int)( (int) $one & (int) $two );
	}
}
?>