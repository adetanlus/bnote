<?php
require_once $GLOBALS["DIR_PRESENTATION_MODULES"] . "gruppenview.php";
require_once $GLOBALS["DIR_DATA_MODULES"] . "gruppendata.php";

/**
 * Special controller for contact module.
 * @author matti
 *
 */
class KontakteController extends DefaultController {
	
	/**
	 * DAO for group submodule.
	 * @var GruppenData
	 */
	private $groupData;
	
	/**
	 * View for submodule.
	 * @var GruppenView
	 */
	private $groupView;
	
	public function start() {
		if(isset($_GET['mode'])) {
			if($_GET['mode'] == "createUserAccount") {
				$this->createUserAccount();
			}
			else if($_GET["mode"] == "groups") {
				$this->groups();
			}
			else if($_GET["mode"] == "integration_process") {
				$this->integrate();
			}
			else {
				$this->getView()->$_GET['mode']();
			}
		}
		else {
			$this->getView()->start();
		}
	}
	
	private function createUserAccount() {
		// create credentials
		$contact = $this->getData()->findByIdNoRef($_GET["id"]);
		
		// find a not taken username
		if($contact['nickname'] != "") {
			$username = $contact['nickname'];
		}
		else {
			$username = $contact["name"] . $contact["surname"];
		}
		$username = strtolower($username);
		
		// fix #173: only allow lower-case letters and numbers (alphanum)
		$username = preg_replace("/[^a-z0-9]/", '', $username);
		
		$i = 2;
		$un = $username;
		while($this->getData()->adp()->doesLoginExist($un)) {
			 $un = $username . $i++;
		}
		$username = $un;
		$password = $this->createRandomPassword(6);
		
		// create user account
		$this->getData()->createUser($_GET["id"], $username, $password);
		
		// check for mail address availibility
		if(isset($contact["email"]) && $contact["email"] != "") {
			// send email
			global $system_data;
			$subject = "Anmeldeinformationen " . $system_data->getCompany();
			
			$body = "Du kannst dich nun unter ";
			$body .= $system_data->getSystemURL() . " anmelden.\n\n";
			$body .= "Dein Benutzername ist " . $username . " und dein ";
			$body .= "Kennwort ist " . $password . " .\n";
			
			// notify user about result
			require_once($GLOBALS["DIR_LOGIC"] . "mailing.php");
			$mail = new Mailing($contact["email"], $subject, $body);
			$mail->setFrom($username . '<' . $contact["email"] . '>');
				
			if(!$mail->sendMail()) {
				$this->getView()->userCredentials($username , $password);
			}
			else {
				$this->getView()->userCreatedAndMailed($username, $contact["email"]);
			}
		}
		else {
			// show credentials & creation success
			$this->getView()->userCredentials($username, $password);
		}
	}
	
	/**
	 * Creates a random password with the given length.
	 * @param int $length Length of password.
	 */
	private function createRandomPassword($length) {
		$chars = "abcdefghijkmnpqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ123456789";
		srand((double)microtime()*1000000);
		$i = 0;
		$pass = '';
		while ($i <= $length) {
			$num = rand() % strlen($chars);
			$tmp = substr($chars, $num, 1);
			$pass = $pass . $tmp;
			$i++;
		}
		return $pass;
	}
	
	private function initGroup() {
		if($this->groupData == null || $this->groupView == null) {
			$this->groupData = new GruppenData();
			$this->groupView = new GruppenView($this);
		}
	}
	
	private function groups() {
		$this->initGroup();
		if(!isset($_GET["func"])) {
			$this->groupView->start();
		}
		else {
			$this->groupView->$_GET["func"]();
		}
	}
	
	function groupOptions() {
		$this->initGroup();
		$this->groupView->showOptions();
	}
	
	function getData() {
		if(isset($_GET["mode"]) && $_GET["mode"] == "groups") {
			return $this->groupData;
		}
		else {
			return parent::getData();
		}
	}
	
	function integrate() {
		$members = GroupSelector::getPostSelection($this->getData()->getMembers(), "member");
		$rehearsals = GroupSelector::getPostSelection($this->getData()->adp()->getFutureRehearsals(), "rehearsal");
		$phases = GroupSelector::getPostSelection($this->getData()->getPhases(), "rehearsalphase");
		$concerts = GroupSelector::getPostSelection($this->getData()->adp()->getFutureConcerts(), "concert");
		$votes = GroupSelector::getPostSelection($this->getData()->getVotes(), "vote");
		
		foreach($members as $cid) {
			foreach($rehearsals as $rid) {
				$res = $this->getData()->addContactRelation("rehearsal", $rid, $cid);
				if($res < 0) {
					new Message("Relation fehlgeschlagen", "Die Relation R$rid - $cid kann nicht gesetzt werden.");
				} 
			}
			foreach($phases as $pid) {
				$this->getData()->addContactRelation("rehearsalphase", $pid, $cid);
				if($res < 0) {
					new Message("Relation fehlgeschlagen", "Die Relation RP$pid - $cid kann nicht gesetzt werden.");
				}
			}
			foreach($concerts as $conid) {
				$this->getData()->addContactRelation("concert", $conid, $cid);
				if($res < 0) {
					new Message("Relation fehlgeschlagen", "Die Relation C$conid - $cid kann nicht gesetzt werden.");
				}
			}
			foreach($votes as $vid) {
				$this->getData()->addContactToVote($vid, $cid);
				if($res < 0) {
					new Message("Relation fehlgeschlagen", "Die Relation V$vid - $cid kann nicht gesetzt werden.");
				}
			}
		}
		
		new Message("Zuordnungen gespeichert", "Die Zuordnungen wurden gespeichert.");
	}
}