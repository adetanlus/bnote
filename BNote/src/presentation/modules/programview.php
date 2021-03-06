<?php

class ProgramView extends CrudView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName("Programm");
	}
	
	/**
	 * Extended version of modePrefix for sub-module.
	 */
	function modePrefix() {
		return "?mod=" . $this->getModId() . "&mode=programs&sub=";
	}
	
	function isSubModule($mode) {
		if($mode == "programs") return true;
		return false;
	}
	
	function subModuleOptions() {
		$subOptionFunc = isset($_GET["sub"]) ? $_GET["sub"] . "Options" : "startOptions";
		if(method_exists($this, $subOptionFunc)) {
			$this->$subOptionFunc();
		}
		else {
			$this->defaultOptions();
		}
	}
	
	function backToStart() {
		$link = new Link("?mod=" . $this->getModId() . "&mode=programs", Lang::txt("back"));
		$link->addIcon("arrow_left");
		$link->write();
	}
	
	function startOptions() {
		$back = new Link("?mod=" . $this->getModId() . "&mode=start", Lang::txt("back"));
		$back->addIcon("arrow_left");
		$back->write();
		
		$this->buttonSpace();
		$add = new Link($this->modePrefix() . "addEntity", "Programm hinzufügen");
		$add->addIcon("plus");
		$add->write();
		
		$this->buttonSpace();
		$addTpl = new Link($this->modePrefix() . "addFromTemplate", "Programm mit Vorlage hinzufügen");
		$addTpl->addIcon("plus");
		$addTpl->write();
	}
	
	function writeTitle() {
		Writing::h2("Programme");
		Writing::p("Klicke auf ein Programm um Details anzuzeigen und die Stücke zu bearbeiten.");
	}
	
	function addFromTemplate() {
		// add the form to insert a program from a template		
		$form = new Form("Program aus Vorlage hinzufügen", $this->modePrefix() . "addWithTemplate");
		$form->addElement("Name", new Field("name", "", FieldType::CHAR));
		$dd = new Dropdown("template");
		$templates = $this->getData()->getTemplates();
		for($i = 1; $i < count($templates); $i++) {
			$dd->addOption($templates[$i]["name"], $templates[$i]["id"]);
		}
		$form->addElement("Vorlage", $dd);
		$form->write();
	}
	
	function showAllTable() {
		$table = new Table($this->getData()->getProgramme());
		$table->removeColumn("id");
		$table->setEdit("id");
		$table->changeMode("programs&sub=view");
		$table->renameHeader("istemplate", "Vorlage");
		$table->setColumnFormat("isTemplate", "BOOLEAN");
		$table->write();
	}
	
	function viewDetailTable() {		
		// program details
		$dv = new Dataview();
		$dv->autoAddElements($this->getData()->findByIdNoRef($_GET["id"]));
		$dv->autoRename($this->getData()->getFields());
		$dv->write();
		
		// track list heading
		Writing::h2("Titelliste");
		
		// actual track list
		$table = new Table($this->getData()->getSongsForProgram($_GET["id"]));
		$table->removeColumn("song");
		$table->renameHeader("rank", "Nr.");
		$table->renameHeader("title", "Titel");
		$table->renameHeader("composer", "Komponist/Arrangeuer");
		$table->renameHeader("length", "Länge");
		$table->renameHeader("notes", "Notizen");
		$table->write();
		$this->writeProgramLength();
	}
	
	private function writeProgramLength() {
		$tt = $this->getData()->totalProgramLength($_GET["id"]);
		Writing::p("Das Programm hat eine Gesamtlänge von <span style=\"font-weight: 600;\">" . $tt . "</span> Stunden.");		
	}
	
	public function view() {
		$this->checkID();
		
		// heading
		Writing::h2($this->getData()->getProgramName($_GET["id"]));
		
		// show the details and tracks
		$this->viewDetailTable();
	}
	
	function additionalViewButtons() {
		$lnk = new Link($this->modePrefix() . "editList&id=" . $_GET["id"], "Titelliste bearbeiten");
		$lnk->addIcon("edit");
		$lnk->write();
		$this->buttonSpace();
		
		$lnk = new Link($this->modePrefix() . "printList&id=" . $_GET["id"], "Titelliste drucken");
		$lnk->addIcon("printer");
		$lnk->write();
		$this->buttonSpace();
		
		$lnk = new Link("src/export/programm.csv?id=" . $_GET["id"], "Titelliste exportieren (CSV)");
		$lnk->addIcon("arrow_down");
		$lnk->setTarget("_blank");
		$lnk->write();
	}
	
	function editList() {
		Writing::h2($this->getData()->getProgramName($_GET["id"]));
		Writing::p("Schiebe die Titel in die Reihenfolge, die du möchtest.");
		
		$tracks = $this->getData()->getSongsForProgram($_GET["id"]);
		echo "<form action=\"" . $this->modePrefix() . "saveList&id=" . $_GET["id"] . "\" method=\"POST\">\n";
		echo "<ul id=\"sortable\">\n";
		for($i = 1; $i < count($tracks); $i++) {
			$text = $tracks[$i]["length"] . "&nbsp;" . $tracks[$i]["title"] . " (" . $tracks[$i]["composer"] . ")";
			$text .= "<input type=\"hidden\" name=\"tracks[]\" value=\"" . $tracks[$i]["psid"] . "\" />\n";
			echo "<li class=\"ui-state-default\">" . '<span class="ui-icon ui-icon-arrowthick-2-n-s"></span>';
			echo $text . "</li>\n";
		}
		echo "</ul>\n";
		$this->writeProgramLength();
		echo "<input type=\"submit\" value=\"SPEICHERN\" />\n";
		echo "</form>\n";
		
		// add and remove tracks
		echo "<table>\n";
		
		echo " <tr>\n";
		echo "  <td colspan=\"2\">"; $this->writeIcon("plus"); echo "Titel hinzufügen</td>\n";
		echo "  <td style=\"width: 20px;\">&nbsp;</td>\n";
		echo "  <td colspan=\"2\">"; $this->writeIcon("remove"); echo "Titel von Programm entfernen</td>\n";
		echo " </tr>\n";
		
		echo " <tr>\n";
		
		// Titel hinzufuegen
		$addTarget = $this->modePrefix() . "addSong&id=" . $_GET["id"];
		
		echo "  <form action=\"$addTarget\" method=\"POST\">\n";		
		$songs = $this->getData()->getAllSongs();
		$dd = new Dropdown("song");
		for($i = 1; $i < count($songs); $i++) {
			$dd->addOption($songs[$i]["title"], $songs[$i]["id"]);
		}
		echo "  <td>" . $dd->write() . "</td>\n";
		echo "  <td><input type=\"submit\" value=\"hinzufügen\" /></td>\n";
		echo "  </form>\n";
		echo "  <td style=\"background-color: #eee;\">&nbsp;</td>\n";
		
		// Titel loeschen
		$delTarget = $this->modePrefix() . "delSong&pid=" . $_GET["id"];
		
		echo "  <form action=\"$delTarget\" method=\"POST\">\n";		
		$songs = $this->getData()->getSongsForProgram($_GET["id"]);
		$dd = new Dropdown("song");
		for($i = 1; $i < count($songs); $i++) {
			$dd->addOption($songs[$i]["title"], $songs[$i]["song"]);
		}
		echo "  <td>" . $dd->write() . "</td>\n";
		echo "  <td>"; echo "<input type=\"submit\" value=\"entfernen\" /></td>\n";
		echo "  </form>\n";
		
		echo " </tr>\n";
		echo "</table>\n";
	}
	
	protected function editListOptions() {
		$back = new Link($this->modePrefix() . "view&id=" . $_GET["id"], Lang::txt("back"));
		$back->addIcon("arrow_left");
		$back->write();
	}
	
	function saveList() {
		foreach ($_POST["tracks"] as $i => $psid) {
			$this->getData()->updateRank($_GET["id"], $psid, $i+1);
			$i++;
		}
		$this->editList();
	}
	
	function addSong() {
		$this->getData()->addSongToProgram($_GET["id"]);
		$this->editList();
	}
	
	function delSong() {
		$this->getData()->deleteSongFromProgram($_GET["pid"], $_POST["song"]);
		$_GET["id"] = $_GET["pid"];
		$this->editList();
	}
	
	function addWithTemplate() {
		$id = $this->getData()->addProgramWithTemplate();
		$_GET["id"] = $id;
		$this->view();
	}
	
	function printList() {
		// heading
		$program = $this->getData()->findByIdNoRef($_GET["id"]);
		Writing::h2($program['name']);
		if($program['notes'] != "") {
			Writing::p($program['notes']);
		}
		
		// print table
		$songs = $this->getData()->getSongsForProgramPrint($_GET["id"]);
		$tab = new Table($songs);
		$tab->renameHeader("title", "Stück");
		$tab->renameHeader("notes", Lang::txt("notes"));
		$tab->renameHeader("length", Lang::txt("length"));
		$tab->addSumLine("Programmlänge", $this->getData()->totalProgramLength());  // automatically uses $_GET["id"]
		$tab->write();
	}
	
	protected function printListOptions() {
		$this->backToViewButton($_GET["id"]);
		$this->buttonSpace();
		
		$print = new Link("javascript:print()", Lang::txt("print"));
		$print->addIcon("printer");
		$print->write();
	}
	
	private function writeIcon($name) {
		echo "<img src=\"" . $GLOBALS["DIR_ICONS"] . $name . ".png\" height=\"15px\" alt=\"\" border=\"0\" />&nbsp;";
	}
}