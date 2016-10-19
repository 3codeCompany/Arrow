<?php
namespace Arrow\Package\Utils;


class DictionaryValidator  extends FormDefaultValidator implements IFormValidator {
    
    public static function getDefault(){
        return new DictionaryValidator();    
    }
    
    public function afterCheck(){
    	//if( isset( $this["id"] ) ) {
    	$id = (int) $this->fieldsValues["key"] ;
    	$id_category = (int) $this->fieldsValues["parent_id"] ;
    	
		if( $id == 0 && $id_category == 0 ) {
			$crit = new Criteria();
			$crit->startGroup( Criteria::C_OR_GROUP);
			$crit->addCondition( UtilsDictionary::F_SYSTEM_NAME, $this->fieldsValues["system_name"] );
			$crit->addCondition( UtilsDictionary::F_SYSTEM_NAME, strtoupper( $this->fieldsValues["system_name"] ) );
			$crit->addCondition( UtilsDictionary::F_SYSTEM_NAME, strtolower( $this->fieldsValues["system_name"] ) );
			$crit->endGroup();
			
			$dic = UtilsDictionary::getByCriteria( $crit, UtilsDictionary::TCLASS );
			if( isset($dic[0]) ) {
    			$this->alerts[] = "Podany słownik istnieje już w bazie danych" ;
            	$this->validationPass = false ;
			}
		}
		
    }
    
}



?>