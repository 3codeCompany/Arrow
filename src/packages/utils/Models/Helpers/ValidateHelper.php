<?php
/* Zestaw funkcji walidujących */

class ValidateHelper {
	
	/* function checkNIP
	 * @params $str (string) - nip do walidacji
	 * @return bool - true jesli poprawny i false jesli niepoprawny nip
	 */
	public static function checkNIP($str) {
		if (strlen($str) != 10) {
			return false;
		} 
		$arrSteps = array(6, 5, 7, 2, 3, 4, 5, 6, 7);
		$intSum=0;
		for ($i = 0; $i < 9; $i++) {
			$intSum += $arrSteps[$i] * $str[$i];
		}
		$int = $intSum % 11;
	 
		$intControlNr=($int == 10)?0:$int;
		if ($intControlNr == $str[9]) {
			return true;
		}
		return false;
	}
	
	
	/* function checkREGON
	 * @params $str (string) - regon do walidacji
	 * @return bool - true jesli poprawny i false jesli niepoprawny regon
	 */
	public static function checkREGON($str) {
		if (strlen($str) != 9) {
			return false;
		}
 
		$arrSteps = array(8, 9, 2, 3, 4, 5, 6, 7);
		$intSum=0;
		for ($i = 0; $i < 8; $i++) {
			$intSum += $arrSteps[$i] * $str[$i];
		}
		$int = $intSum % 11;
		$intControlNr=($int == 10)?0:$int;
		if ($intControlNr == $str[8]) {
			return true;
		}
		return false;
	}
	
	/* Sprawdza czy długość stringu jest poprawna
	 * @param (int) $min - minimalna ilość znaków
	 * @param (int) $max - maksymalna ilość znaków
	 * @return (bool) - true jeśli string spełnia wymagania false w przeciwnym razie 
	 */
	public static function checkLength( $str, $min, $max = PHP_INT_SIZE ) {
		$len = strlen( $str ) ;
		if( $len >= $min && $len <= $max ) return true;
		else return false; 
	}
	
	public static function validEmail($email){
        if(preg_match('/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$/',$x))return true;
        return false;
    } 
	
	public static function validate( $type, $value ) {
		
		switch( $type ) {
			
			
		}
		
		
	}
	
}


?>