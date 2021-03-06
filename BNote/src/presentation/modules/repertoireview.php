<?php
/**
 * View for repertoire module.
 * @author matti
 *
 */
class RepertoireView extends CrudRefView {

	/**
	 * Create the repertoire view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName("Song");
		$this->setJoinedAttributes(RepertoireData::getJoinedAttributes());
	}
	
	function showOptions() {
		if(isset($_GET["mode"]) && $_GET["mode"] == "genre") {
			$this->getController()->getGenreView()->showOptions();
		}
		else {
			parent::showOptions();
		}
	}
	
	protected function startOptions() {
		parent::startOptions();
		$this->buttonSpace();
		if(isset($_GET["showFilters"]) && $_GET["showFilters"] == "true") {
			$filterbox = new Link($this->modePrefix() . "start", "Filter ausblenden");
		}
		else {
			$filterbox = new Link($this->modePrefix() . "start&showFilters=true", "Filter anzeigen");
		}
		$filterbox->addIcon("filter");
		$filterbox->write();
		
		$this->buttonSpace();
		$massChange = new Link($this->modePrefix() . "massUpdate", "Mehrere Songs ändern");
		$massChange->addIcon("edit");
		$massChange->write();
		
		$this->buttonSpace();
		$genre_mod = new Link($this->modePrefix() . "genre&func=start", "Genres verwalten");
		$genre_mod->addIcon("music_folder");
		$genre_mod->write();
		
		$this->buttonSpace();
		$xlsImport = new Link($this->modePrefix() . "xlsUpload", "Excel Import");
		$xlsImport->addIcon("arrow_down");
		$xlsImport->write();
	}
	
	protected function addEntityForm() {
		?>
		<script type="text/javascript">
		$(function() {
			var composers = [
			    <?php
			    echo $this->getData()->listComposers();
			    ?>
			];

			$("#composer").autocomplete({
				source: composers
			});
		});	
		</script>
		<?php
		$form = new Form("Song hinzufügen", $this->modePrefix() . "add&manualValid=true");
		$form->autoAddElementsNew($this->getData()->getFields());
		
		$form->removeElement("id");
		$form->setForeign("genre", "genre", "id", "name", -1);
		$form->setForeign("status", "status", "id", "name", -1);
		
		$form->removeElement("composer");
		$composer = "<input type=\"text\" name=\"composer\" id=\"composer\" size=\"30\" />";
		$form->addElement("Komponist / Arrangeur", new TextWriteable($composer));
		
		$form->removeElement("length");
		$length = "<input type=\"text\" name=\"length\" size=\"6\" />&nbsp;min";
		$form->addElement("Länge", new TextWriteable($length));
		
		$form->write();
	}
	
	protected function showAllTable() {
		// Filters
		if(isset($_GET["showFilters"])) {
			$filter = new Filterbox($this->modePrefix() . "start&showFilters=true&filters=true");
			$filter->addFilter("genre", "Genre", FieldType::SET, $this->getData()->getGenres());
			$filter->addFilter("music_key", "Tonart", FieldType::CHAR, "");
			$filter->addFilter("solist", "Solist", FieldType::SET, $this->getData()->getAllSolists());
			$filter->setNameCols("solist", array("name", "surname"));
			$filter->addFilter("status", "Status", FieldType::SET, $this->getData()->getStatuses());
			$filter->addFilter("composer", "Komponist", FieldType::SET, $this->getData()->getComposers());
			$filter->write();
			$this->verticalSpace();
		}
		
		if(isset($_GET["filters"]) && $_GET["filters"] == "true") {
			$data = $this->getData()->getFilteredRepertoire($_POST);
		}
		else {
			$data = $this->getData()->findAllJoinedWhere($this->getJoinedAttributes(), "length >= 0 ORDER BY title");
		}
		
		// Table
		$table = new Table($data);
		$table->setEdit("id");
		$table->renameAndAlign($this->getData()->getFields());
		$table->renameHeader("genrename", "Genre");
		$table->renameHeader("composername", "Komponist/Arrangeur");
		$table->renameHeader("statusname", "Status");
		$table->removeColumn("id");
		$table->removeColumn("notes");
		$table->write();
		
		// Length
		$tt = $this->getData()->totalRepertoireLength();
		Writing::p("Das Repertoire hat eine Gesamtlänge von <strong>" . $tt . "</strong> Stunden.");
	}
	
	public function view() {
		$song = $this->getData()->findByIdJoined($_GET["id"], $this->getJoinedAttributes());
		?>
		<h1><?php echo $song["title"]; ?> <span class="repertoire_song_composer_title"> <?php echo $song["composername"]; ?></span></h1>
		
		<div class="repertoire_song_detailbox">
			<div class="songbox_col">
				<div class="songbox_entry">
					<div class="songbox_label">Tonart</div>
					<div class="songbox_value"><?php echo $song["music_key"]; ?></div>
				</div>
				
				<div class="songbox_entry">
					<div class="songbox_label">Länge</div>
					<div class="songbox_value"><?php echo $song["length"]; ?></div>
				</div>
				
				<div class="songbox_entry">
					<div class="songbox_label">Tempo</div>
					<div class="songbox_value"><?php echo $song["bpm"]; ?></div>
				</div>
			</div>
			<div class="songbox_col">
				<div class="songbox_entry">
					<div class="songbox_label">Status</div>
					<div class="songbox_value"><?php echo $song["statusname"]; ?></div>
				</div>
				
				<div class="songbox_entry">
					<div class="songbox_label">Genre</div>
					<div class="songbox_value"><?php echo $song["genrename"]; ?></div>
				</div>
				
				<div class="songbox_entry">
					<div class="songbox_label">Besetzung</div>
					<div class="songbox_value"><?php echo $song["setting"]; ?></div>
				</div>
			</div>
			<div class="songbox_col">
				<div class="songbox_entry">
					<div class="songbox_label">Anmerkungen</div>
				</div>
				<div class="songbox_areavalue"><?php echo $song["notes"]; ?></div>
			</div>
		</div>
		
		<div class="repertoire_song_extra">
			<div class="songextra_col">
				<h2>Referenzen</h2>
				
				<h3>Stück in Proben</h3>
				<ul>
					<?php 
					// References
					$references = $this->getData()->findReferences($_GET["id"]);
					for($i = 1; $i < count($references["rehearsals"]); $i++) {
						$reh = $references["rehearsals"][$i];
						echo "<li>" . Data::convertDateFromDb($reh["begin"]) . "</li>";
					}
					?>
				</ul>
				
				<h3>Stück in Auftritten</h3>
				<ul>
					<?php
					for($i = 1; $i < count($references["concerts"]); $i++) {
						$con = $references["concerts"][$i];
						$title = $con["title"];
						echo "<li>" . Data::convertDateFromDb($con["begin"]) . " / $title</li>";
					}
					?>
				</ul>
			</div>
			<div class="songextra_col">
				<?php 
				// Files
				if($this->getData()->getSysdata()->userHasPermission(12)) {
					$this->songFiles();
				}
				?>
			</div>
			<div class="songextra_col">
				<h2>Solisten</h2>
				<ul>
					<?php 
					// Solists
					$solists = $this->getData()->getSolists($_GET["id"]);
					// add a link to the data to remove the solist from the list
					for($i = 1; $i < count($solists); $i++) {
						$sol = $solists[$i];
						$delLink = $this->modePrefix() . "delSolist&id=" . $_GET["id"] . "&solistId=" . $solists[$i]["id"];
						$btn = new Link($delLink, "");
						$btn->addIcon("remove");
						echo "<li>" . $sol["name"] . " " . $sol["surname"] . " (" . $sol["instrument"] . ") " . $btn->toString() . "</li>";
					}
					if(count($solists) == 1) {
						?>
						<li>Keine Solisten angegeben.</li>
						<?php
					}
					?>
				</ul>
			</div>
		</div>
		<?php
	}
	
	private function songFiles() {
		$songs = $this->getData()->getFiles($_GET["id"]);
		?>
		<div class="songfiles_box">
			<h3>Dateien</h3>
			<ul>
			<?php
			// show files
			for($i = 1; $i < count($songs); $i++) {
				$file = $songs[$i]["filepath"];
				$href = "src/data/filehandler.php?file=/" . $file;
				$preview = $href;
				$delHref = $this->modePrefix() . "removeSongFile&id=" . $_GET["id"] . "&songfile=" . $songs[$i]["id"];
				$imgWidth = "50px";
				?>
				<li class="songfiles_filebox">
					<?php
					if(!Data::endsWith($file, "png") && !Data::endsWith($file, "jpg") && !Data::endsWith($file, "jpeg") && !Data::endsWith($file, "bmp")) {
						$preview = "style/icons/copy_link.png";
						$imgWidth = "32px";
					}
					?>
					<a href="<?php echo $href; ?>" target="_blank">
						<img class="songfiles_preview" src="<?php echo $preview; ?>" width="<?php echo $imgWidth; ?>">
					</a>
					<div class="songfiles_textbox">
						<a class="songfiles_filelink" href="<?php echo $href; ?>" target="_blank"><?php echo $file; ?></a><br/>
						<a href="<?php echo $delHref; ?>">Verknüpfung löschen</a>
					</div>
				</li>
				<?php
			}
			?>
			</ul>
			
			<?php
			// add files form
			$form = new Form("Datei hinzufügen", $this->modePrefix() . "addSongFile&id=" . $_GET["id"]);
			$dd = new Dropdown("file");
			$possibleFiles = $this->getData()->getShareFiles();
			foreach($possibleFiles as $i => $fileinfo) {
				$dd->addOption($fileinfo["filename"], $fileinfo["fullpath"]);
			}
			$form->addElement("", $dd);
			$form->write();
			?>
		</div>
		<?php
	}
	
	public function addSongFile() {
		$songId = $_GET["id"];
		$fullpath = $_POST["file"];
		$this->getData()->addFile($songId, $fullpath);
		$this->view();
	}
	
	function addSongFileOptions() {
		$this->viewOptions();
	}
	
	function removeSongFile() {
		$this->getData()->deleteFileReference($_GET["songfile"]);
		$this->view();
	}

	function removeSongFileOptions() {
		$this->viewOptions();
	}
	
	protected function additionalViewButtons() {
		$addSol = new Link($this->modePrefix() . "addSolist&id=" . $_GET["id"], "Solist hinzufügen");
		$addSol->addIcon("plus");
		$addSol->write();
	}
	
	protected function editEntityForm($write=true) {
		$song = $this->getData()->findByIdNoRef($_GET["id"]);
		
		$form = new Form("Song bearbeiten", $this->modePrefix() . "edit_process&manualValid=true&id=" . $_GET["id"]);
		$form->autoAddElements($this->getData()->getFields(), $this->getData()->getTable(), $_GET["id"]);
		$form->removeElement("id");
		$form->renameElement("length", "Länge in Stunden");
		$form->setForeign("genre", "genre", "id", "name", $song["genre"]);
		$form->setForeign("status", "status", "id", "name", $song["status"]);
		$form->removeElement("composer");
		$form->addElement("Komponist/Arrangeur", new Field("composer",
					$this->getData()->getComposerName($song["composer"]), FieldType::CHAR));
		
		if($write) {
			$form->write();
		}
		return $form;
	}
	
	function addSolist() {
		$this->checkID();
		
		$form = new Form("Solisten auswählen", $this->modePrefix() . "process_addSolist&id=" . $_GET["id"]);
		$contacts = $this->getData()->adp()->getContacts();
		$selector = new GroupSelector($contacts, array(), "solists");
		$selector->setNameColumns(array("name", "surname"));
		$form->addElement("Solisten", $selector);
		$form->write();
	}
	
	function process_addSolist() {
		$this->getData()->addSolist($_GET["id"]);
		new Message("Solist hinzugefügt", "Der Solist wurde dem Stück hinzugefügt.");
		$this->backToViewButton($_GET["id"]);
	}
	
	function delSolist() {
		$this->getData()->deleteSolist($_GET["id"], $_GET["solistId"]);
		$this->view();
	}
	
	function xlsUpload() {
		// file upload
		$form = new Form("Excel Dateiupload", $this->modePrefix() . "xlsMapping");
		$form->addElement("XLSX Datei (Excel 2007+)", new Field("xlsfile", "", FieldType::FILE));
		$form->setMultipart(true);
		$form->changeSubmitButton("Hochladen und weiter");
		$form->write();
	}
	
	function xlsMapping($data, $header) {		
		// show which columns were detected and allow mapping
		$form = new Form("Spalten zuweisen", $this->modePrefix() . "xlsImport");
		
		// create column selector
		$form->addElement("Titel", $this->columnSelector("col_title", $header));
		$form->addElement("Komponist/Arrangeur", $this->columnSelector("col_composer", $header));
		$form->addElement("Tonart", $this->columnSelector("col_key", $header));
		$form->addElement("Tempo (BPM)", $this->columnSelector("col_tempo", $header));
		$form->addElement("Notizen", $this->columnSelector("col_notes", $header));
		
		// Status
		$dd_status = new Dropdown("status");
		$stati = $this->getData()->getStatuses();
		for($i = 1; $i < count($stati); $i++) {
			$dd_status->addOption($stati[$i]["name"], $stati[$i]["id"]);
		}
		$form->addElement("Status", $dd_status);
		
		// Genre
		$genres = $this->getData()->getGenres();
		$dd_genre = new Dropdown("genre");
		for($i = 1; $i < count($genres); $i++) {
			$dd_genre->addOption($genres[$i]["name"], $genres[$i]["id"]);
		}
		$form->addElement("Gerne", $dd_genre);
		
		// finalize form
		$form->changeSubmitButton("Weiter");
		$xlsData = urlencode(json_encode($data));
		$form->addHidden("xlsData", $xlsData);
		$form->write();
	}
	
	protected function columnSelector($fieldname, $header) {
		$dd = new Dropdown($fieldname);
		
		$dd->addOption("- Nicht importieren", "-1");
		foreach($header as $idx => $name) {
			$n = $name;
			if($n == "") {
				$n = "(unnamed)";
			}
			$dd->addOption($n, $idx);
		}
		
		$dd->setSelected("-1");
		return $dd;
	}
	
	function xlsImport($duplicates, $num_rows, $empties) {
		// show how many can be imported directly
		Writing::h2("Import");
		Writing::p("$num_rows Zeilen können direkt importiert werden. " . 
				count($empties) . " Zeilen enthalten keinen Titel und wurden als leer gekennzeichnet.");
		
		// show duplicates and ask to overwrite (use from sheet) or ignore (use from BNote) for each
		$form = new Form("Duplikate", $this->modePrefix() . "xlsProcess");
		foreach($duplicates as $idx => $row) {
			$name = $row[$_POST["col_title"]];
			$element = new Dropdown("duplicate_$idx");
			$element->addOption("Überschreiben", $row["duplicate_id"]);
			$element->addOption("Ignorieren", -1);
			$form->addElement($name, $element);
		}
		if(count($duplicates) == 0) {
			$form->addElement("Keine Duplikate erkannt", new Field("", "", 99));
		}
		$form->addHidden("empties", join(",", $empties));
		
		// add data from previous form
		foreach($_POST as $k => $v) {
			$form->addHidden($k, $v);
		}
		
		$form->write();
	}
	
	function xlsProcessSuccess() {
		new Message("Import erfolgreich", "Die Stücke wurden erfolgreich importiert.");
	}
	
	function massUpdate() {
		// setup form
		$form = new Form("Songs bearbeiten", $this->modePrefix() . "process_massUpdate&manualValid=true");
		
		// select what to change
		$form->autoAddElementsNew($this->getData()->getFields());
		$toRemove = array("id", "notes", "title", "length", "composer");
		foreach($toRemove as $i => $field) {
			$form->removeElement($field);
		}
		
		$form->setForeign("genre", "genre", "id", array("name"), 0);
		$form->addForeignOption("genre", "[Nicht ändern]", 0);
		
		$form->setForeign("status", "status", "id", array("name"), 0);
		$form->addForeignOption("status", "[Nicht ändern]", 0);
		
		// select the song
		$songs = $this->getData()->findAllJoined($this->getJoinedAttributes());
		$songSelector = new GroupSelector($songs, array(), "songs");
		$songSelector->setNameColumns(array("title"));
		$form->addElement("Songs", $songSelector);
		
		// show form
		$form->write();
	}
	
	function process_massUpdate() {
		$this->getData()->massUpdate();
		new Message("Songs speichert", "Die Songs wurden erfolgreich aktualisiert.");
	}
}

?>