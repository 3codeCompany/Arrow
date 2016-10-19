<?php


class DBLoader extends \Arrow\Object{
	
	const CONF_FILE = "/conf/db-data.xml" ;
	
	public static function LoadDB() {
		$req = RequestContext::getDefault();
		$project = \Arrow\Controller::$project ;
		$file = $project->getPath() . self :: CONF_FILE;
		$xml = simplexml_load_file( $file ) ;
		
		$wd = self::getWithData( $xml );

		$mode = "" ;
		if( isset($req["mode"]) ) $mode = $req["mode"]; 
		
		if( count( $wd ) > 0 && $mode == "" ) {
$txt = <<<TXT
<div style="width:700px;margin:auto;margin-top:50px;" >
<h2> pewne tabele do ktorych mam zamiar dodac wpisy sa pelne</h2>
<h1> Co mam zrobic ??</h1>
<ul>
<li><h3><a href="javascript:window.location.href += '&mode=clean'; " >Wyczysc te tabele i dodaj dane</a></h3></li>
<li><h3><a href="javascript:window.location.href += '&mode=empty'; " >Dodaj dane tylko do pustych tabel</a></h3></li>
</ul>
</div>
TXT;
			echo $txt ;
			return;
		} else {
			
			foreach( $xml as $object ) {
				$table = (string) $object["table"] ;
				if( self::areData( $table ) ) {
					if( $mode=="clean" ) {
						SqlRouter::query( "TRUNCATE TABLE `$table`;" , "cms" ) ;
					}else break;
				
				}
				foreach( $object->row as $row ) {
						$insert = self::buildInsert( $table, $row->val ) ;
						SqlRouter::query( $insert, "cms" ) ;
				}
			}
		}
		
	}
	
	private static function buildInsert( $table, $values ) {
		$cols = array();
		$vals = array() ;
		
		foreach( $values as $v ){
			$cols[] = (string) "`{$v["name"]}`";
			if( isset($v["function"]) ) {
				$vals[] = "'".self::madeFunction( $v["function"] , $v["value"] )."'"  ;
			} else
				$vals[] = (string) "'{$v["value"]}'";	
		}
		
		return "INSERT INTO $table (".implode(", ", $cols).") VALUES (".implode(", ", $vals ).")" ; ;
		
	}
	
	
	private static function madeFunction( $function, $data ) {
		switch( $function ) {
			case "password":
				return User::generatePassword( $data ) ;
				break;
			case "passport":
				return User::generatePassportId( array( "login" => $data ) );
				break;
			default:
				throw new \Arrow\Exception( "Brak Funkcji konwertujacej '$function'"  ) ;
		}
	}
	
	private static function getWithData( $data ) {
		$ret = array() ;
		foreach( $data as $d ) {
			$table = (string) $d["table"] ;
			$data = SqlRouter::toArray( SqlRouter::query( "SELECT * FROM $table LIMIT 1;" , "cms" ) ) ;
			if( isset($data[0]) ) $ret[$table] = $table ;	
		}
		return $ret;
	}
	
	private static function areData( $table ) {
		$data = SqlRouter::toArray( SqlRouter::query( "SELECT * FROM $table LIMIT 1;" , "cms" ) ) ;
		if( isset($data[0]) ) return true;
		return false;	
	}
	
}
?>