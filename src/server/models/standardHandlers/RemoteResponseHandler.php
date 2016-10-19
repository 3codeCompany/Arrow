<?php namespace Arrow\Models;


/**
 * Array conversion 
 * 
 * @package  Arrow
 * @license  GNU GPL
 * @author   Artur Kmera <artur.kmera@arrowplatform.org> 
 */

class RemoteResponseHandler implements IRemoteResponseHandler {

	/**
	 * Singleton
	 *
	 * @var object
	 */
	private static $selfInstance = false;

	/**
	 * Return object instance
	 *
	 * @return Singleton
	 * @access public
	 * @static
	 */
	public static function getDefault() {
		if (self :: $selfInstance == false) {
			self :: $selfInstance = new RemoteResponseHandler();
		}
		return self :: $selfInstance;
	}

	/**
	 * Konstruktor. Tworzy nowy dokument DOM.
	 * 
	 * @return void
	 */
	private function __construct() {

		$this->dom = new DOMDocument("1.0", "utf-8");
		$this->dom->formatOutput = true;
		$this->domExport = $this->dom->appendChild($this->dom->createElement("arrowResponse"));

	}

	/**
	 * Zmienia tablecę w dokument DOM
	 * 
	 * @param array $array Zmieniana tablica.
	 * @param DOMNode $parentNode Węzeł - rodzic - dla rekursywnego wywołania. 
	 */ 
	private function parse($array, $parentNode = false) {
		
		if( is_object($array) && (in_array("IObjectSerialize", class_implements($array)) || $array instanceof Persistent ) ){
			$array = $array->serialize();
		}

		$parentNode = ($parentNode) ? $parentNode : $this->domExport;
	
		foreach ($array as $key => $value) {
			
			if ((is_array($value) || is_object($value)) && !is_numeric($key)) {

				$element = $parentNode->appendChild($this->dom->createElement($key));

				$this->parse($value, $element);

			}
			elseif ((is_array($value) || is_object($value)) && is_numeric($key)) {

				$element = $parentNode->appendChild($this->dom->createElement("list"));

				$element->setAttribute("index", $key);

				$this->parse($value, $element);

			}
			elseif (is_numeric($key)) {

				$element = $this->dom->createElement("list");
				//echo $key." => ". $value ."<br />";
				$element->appendChild($this->dom->createTextNode($value));
				
				$element->setAttribute("index", $key);

				$parentNode->appendChild($element);

			} else {

				$element = $this->dom->createElement($key);

				if ($key == "source") {
					$element->appendChild($this->dom->createCDATASection($value));
				} else {
					$element->appendChild($this->dom->createTextNode($value));
				}

				$parentNode->appendChild($element);

			}

		}

		return $parentNode;

	}

	/**
	 * Zmienia tablicę w XML
	 * 
	 * @param array $array Tablica do zmiany.
	 */
	public function getResponse($array) {

		if(is_array($array) || is_object($array)){
			$this->parse($array);
		}else{
			$this->domExport->appendChild($this->dom->createElement("singleResponse", $array));
		}
		
		$string = $this->dom->saveXML();
		
		return $string;

	}

	/**
	 * Ustawia nagłówki pzeglądarki do odczytu dokumentu xml.
	 * 
	 * @return void
	 */
	function setHeaders() {

		header("Content-type: text/xml");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");

	}
}
?>