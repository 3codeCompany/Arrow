<?php
namespace Arrow\Package\Access;

class UserFormValidator extends \Arrow\Controls\FormDefaultValidator{
	
	public static function getDefault(){
		return new UserFormValidator();
	}
	
	public function afterCheck(){
		
		if( $this->fieldsValues["key"] == "" || $this->fieldsValues["password"] != "" || $this->fieldsValues["repassword"] != "" ) {

			if( $this->fieldsValues["password"] == "" ) {
				$this->alerts[] = "Wprowadź hasło";
				$this->validationPass = false;
			}
			if( $this->fieldsValues["repassword"] == "" ) {
				$this->alerts[] = "Wprowadź powtórzenie hasła";
				$this->validationPass = false;
			}
			if( $this->fieldsValues["password"] != $this->fieldsValues["repassword"] ) {
				$this->alerts[] = "Hasło i jego powtórzenie nie zgadzają się";
				$this->validationPass = false;
			}
		}

        if($this->fieldsValues["key"] == ""){
            $user = \Arrow\ORM\Persistent\Criteria::query("Arrow\Package\Access\User")->c("login", $this->fieldsValues["login"])->findFirst();
            if(!empty($user)){
                $this->alerts[] = "Podany login już istnieje w bazie użytkowników";
                $this->validationPass = false;
            }
        }

		
	}
}
?>