<?php

/**
 * Superclass for all module views.
 * @author matti
 *
 */
abstract class AbstractView {
	
	private $controller;
	
	/**
	 * Name of the parameter that is used in the URL to represent the ID of the record.
	 * @var String
	 */
	protected $idParameter = "id";
	
	/**
	 * Name of the ID field in the record from the database.
	 * @var String
	 */
	protected $idField = "id";
	
	/**
	 * Entry Point for any view.
	 */
	abstract function start();
	
	/**
	 * Contains the buttons with the options that are available on this page/view.
	 */
	function showOptions() {
		if(isset($_GET["mode"])) {
			$mode = $_GET["mode"];
		}
		else {
			$mode = "start";
		}
		
		$opt = $mode . "Options";
		if(method_exists($this, $opt)) {
			$this->$opt();
		}
		else {
			// no button on start page
			if(!$this->isMode("start")) {
				$this->backToStart();
			}
		}
	}
	
	/**
	 * Write a button with caption "Back" to bring the user back to the
	 * module's home screen.
	 */
	public function backToStart() {
		global $system_data;
		$link = new Link("?mod=" . $system_data->getModuleId(), Lang::txt("back"));
		$link->addIcon("arrow_left");
		$link->write();
	}
	
	/**
	 * Prints the confirmation message with the buttons. The back-button links to
	 * the view mode with the given ID in the $_GET array. The delete-button links
	 * to the delete mode.
	 * @param String $label Name of the entity to remove, e.g. "user" or "project" 
	 * @param String $linkBack The link the back button links to, usually to the view-mode (by default not shown).
	 * @param String $linkDelete The link the confirmation links to, usually to the delete-mode.
	 */
	protected function deleteConfirmationMessage($label, $linkDelete, $linkBack = null) {
		new Message($label . Lang::txt("delete") . "?", Lang::txt("reallyDeleteQ"));
		$yes = new Link($linkDelete, strtoupper($label) . " " . strtoupper(Lang::txt("delete")));
		$yes->addIcon("remove");
		$yes->write();
		$this->buttonSpace();
		
		if($linkBack != null) {
			$no = new Link($linkBack, Lang::txt("back"));
			$no->addIcon("arrow_left");
			$no->write();
		}
	}
	
	/**
	 * Checks whether $_GET["id"] is set, otherwise terminates with error.
	 */
	public function checkID() {
		if(!isset($_GET[$this->idParameter])) {
			new BNoteError(Lang::txt("noUserId"));
		}
	}
	
	/**
	 * Convenience function.
	 * @return A string like "?mod=<id>&mode=". Append the mode and other GET parameters.
	 */
	protected function modePrefix() {
		return "?mod=" . $this->getModId() . "&mode=";
	}
	
	/**
	 * Creates a dropdown widget with all 12 months of a year.
	 * @param String $name The identifier of the select-tag.
	 */
	protected function createMonthDropdown($name) {
		$dd = new Dropdown($name);
			foreach(Data::getMonths() as $num => $name) {
				$dd->addOption($name, $num);
			}
		return $dd;
	}
	
	/**
	 * Creates a dropdown widget with all year from the selection.
	 * @param Array $years Selection object with all (distinct) years in the column "year".
	 * @param String $name The identifier of the select-tag.
	 */
	protected function createYearDropdown($years, $name) {
		$dd = new Dropdown($name);
		$count = 0;
		foreach($years as $row => $y) {
			if($count == 0) {
				$count++;
				continue;
			}
			$dd->addOption($y["year"], $y["year"]);
		}
		return $dd;
	}
	
	/**
	 * Checks the given keys whether it contains the needle.
	 * @param String $needle Contains-String to search for.
	 * @param Array $keyArray Keys.
	 * @return Name of the first occurance of a fuzzy key or null if not found.
	 */
	private function fuzzyKeySearch($needle, $keyArray) {
		foreach($keyArray as $i => $key) {
			if(substr_count($key, $needle) > 0) return $key;
		}
		return null;
	}
	
	/**
	 * Searches the given array for the fields "city", "zip" and "street".
	 * Then builds a string with the given address. In case a field cannot
	 * be found, it is omitted.
	 * @param Array $row Should contain at least one of the fields "city", "zip" or "street".
	 */
	protected function buildAddress($row) {		
		$rowKeys = array_keys($row);
		if(!isset($row["street"])) {
			// look for a street field in the keys
			$likeKey = $this->fuzzyKeySearch("street", $rowKeys);
			if($likeKey != null) $street = $row[$likeKey];
			else $street = "";
		}
		else $street = $row["street"];
		
		if(!isset($row["city"])) {
			// look for a city field in the keys
			$likeKey = $this->fuzzyKeySearch("city", $rowKeys);
			if($likeKey != null) $city = $row[$likeKey];
			else $city = "";
		}
		else $city = $row["city"];
		
		if(!isset($row["zip"])) {
			// look for a zip field in the keys
			$likeKey = $this->fuzzyKeySearch("zip", $rowKeys);
			if($likeKey != null) $zip = $row[$likeKey];
			else $zip = "";
		}
		else $zip = $row["zip"];
		
		/*
		 * street & city
		 * street & zip -> ignored, only street then
		 * city & zip
		 * only street
		 * only city
		 * only zip -> ignored, nothing then
		 */
		if($street != "" && $city != "" && $zip != "") {
			return $street . ", " . $zip . " " . $city;
		}
		else if($street != "" && $city != "") {
			return $street . ", " . $city;
		} 
		else if($street != "") {
			return $street;
		}
		else if($city != "" && $zip != "") {
			return $zip . " " . $city;
		}
		else if($city != "") {
			return $city;
		}
		
		return "";
	}
	
	/**
	 * Prints the string which contrains the space inbetween buttons.
	 */
	static function buttonSpace() {
		//none
	}
	
	protected function setController($ctrl) {
		$this->controller = $ctrl;
	}
	
	protected function getController() {
		return $this->controller;
	}
	
	/**
	 * Returns the data component for this module.
	 * @return AbstractData
	 */	
	protected function getData() {
		return $this->controller->getData();
	}
	
	protected function getModId() {
		global $system_data;
		return $system_data->getModuleId();
	}
	
	/**
	 * Prints two br-tags.
	 */
	public static function verticalSpace() {
		echo "<br /><br />\n";
	}
	
	protected function isMode($mode) {
		return (isset($_GET["mode"]) && $_GET["mode"] == $mode);
	}
	
	/**
	 * Print a flash message.
	 * @param String $message Message body.
	 * @param string $level Level: info, warn, error
	 */
	public static function flash($message, $level="warn") {
		?>
		<div class="flash_message <?php echo $level; ?>"><?php echo $message; ?></div>
		<?php
	}
}

?>