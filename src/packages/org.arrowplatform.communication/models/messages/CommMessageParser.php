<?php





class CommMessageParser {
	
	/**
	 * 
	 * @param CommMessage $message - wiadomość
	 * @param string $box_email - email z którego przyszła wiadomość
	 * @return zwraca true jeśli znaleziono powiązanie, false jeśli nie
	 */
	public static function parse( CommMessage $message, $box_email, $atachments ) {
		$data = array();
		
		$email_to_check = array() ;
		if( $message[ CommMessage::F_TYPE]  == CommMessage::MESSAGE_IN ) {  // wiadomość przychodząca
			$emails_to_check = $message->getEmailsFrom() ;
			//$emails_to_check = array_merge( $emails_to_check, $message->getEmailsTo() ) ;		// TODO: wprowadzić łaczenie po To
			$data[Contact::F_TYPE] = Contact::TYPE_IN ;
			$kind_system_name = "email_przychodzacy";
		} else {  // wiadomość wychodząca
			$emails_to_check = $message->getEmailsTo() ;
			$data[Contact::F_TYPE] = Contact::TYPE_OUT ;
			$kind_system_name = "email_wychodzacy";
		}
		
		//FB::log( $emails_to_check );
		
		$crit = new Criteria( );
		$crit->addCondition( ContactNo::F_VALUE, $emails_to_check, Criteria::C_IN ) ;
		$crit->addCondition( ContactNo::F_TMP_ID, 0 ) ;
		
		$cns = ContactNo::getByCriteria( $crit, ContactNo::TCLASS );
		//FB::log( count( $cns ) , "kontakty" );
		if( !empty( $cns ) ) {
			$company = array() ;
			$user = Auth::getDefault()->getUser();
			$me_person_id = $user->getPersonId( $user["id"] )  ;
			$persons = array( ) ;
			$persons[ $me_person_id ] = $me_person_id ;
			
			foreach( $cns as $c ){
				switch( $c["class"] ) {
					case "Company" :
							$company[ $c["id_object"] ] = $c["id_object"] ;
						break;
					case "Person" :
							$persons[ $c["id_object"] ] = $c["id_object"] ;
						break;
					default:
						throw new \Arrow\Exception(array("msg" =>"[CommMessageParser] Brak obsługi powiązania z {$c["class"]}") );
				}
			}
			
			
			
			if( count( $company ) > 0 || count( $persons ) > 0 ) {  // jeśli istnieje conajmniej jedno powiązanie 
				if( count( $company ) == 0 && count( $persons ) > 0 ) { // pobierz z pierwszego
					$mother_company = \Arrow\Models\Settings::getDefault()->getSetting( "global.app.company" ) ;
					foreach( $persons as $person ) {    // wyszukanie pierwszej firmy z którą powiązana jest osoba i nie jest to firma matka
						$pers = Person::getByKey( $person, Person::TCLASS ) ;
						if( $pers["company_id"] > 0 && $pers["company_id"] != $mother_company  ) {
							$company[ $pers["company_id"] ] = $pers["company_id"] ;
							break ;
						}
					}
					
				}
				
				if( count($company) > 1 ) throw new MessageException( "[CommMessageParser] email jest powiązany z więcej niż jedną firmą" ) ;
				
				$company_id = reset( $company ) ;
				
				/*echo "<pre>" ;
				print_r($company_id);
				print_r($emails_to_check);
				print_r($cns);
				exit; */
				
				if( $company_id > 0 )
				$data[Contact::F_ID_COMPANY] = $company_id ; 
				
				$data[Contact::F_NAME] = $message[CommMessage::F_SUBJECT] ;
				$data[Contact::F_V_PRIVACY] = "global" ;
				
				$dict_kind = UtilsDictionary::getDictionary( $kind_system_name, "contact_kind", true );
				$data[Contact::F_ID_KIND] = $dict_kind["id"];
				
				$dict_from = UtilsDictionary::getDictionary( "email", "form_contact", true );
				$data[Contact::F_ID_FORM] = $dict_from["id"];
				
				$data[Contact::F_DATE] = $message[CommMessage::F_DATE] ;
				if( !empty( $message[CommMessage::F_HTML] ) )
					$data[Contact::F_CONTENT] = $message[CommMessage::F_HTML] ;
				else 
					$data[Contact::F_CONTENT] = "<pre>".$message[CommMessage::F_PLAIN]."</pre>" ;
					
				$data[Contact::F_TMP_ID] = 0 ;	
				// Zapisanie 
				$data[Contact::F_ID_MAIL] = $message[CommMessage::F_ID];
				$contact = Contact::create( $data, Contact::TCLASS );
				$contact->save();
				
				//FB::log( $contact->serialize(), "conn" );
				
				// Dodanie osób zaangarzowanych
				foreach( $persons as $person ) {
					$contact->doAction( "add_urzytkownik", array( "person_id" => $person ) ) ;
				}
				
				
				// Dodanie załączników
				foreach( $atachments as $attachment ) {
					MediaApi::addFileToObject( $contact, $attachment["path"] ) ;
				}
				
				// uwzględnienie powiązania w wiadomości
				$message[ CommMessage::F_CONTACT_ID ] = $contact["id"] ;
				$message->save();
				
				
				return true;
			}
		}
		return false ;
	}
	
}
?>