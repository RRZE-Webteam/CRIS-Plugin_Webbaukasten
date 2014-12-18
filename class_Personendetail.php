<?php

class Personendetail {

	public function __construct() {

		$url = explode('/',$_SERVER['REQUEST_URI']);
		$ID = $url[count($url)-1]; //letztes Element der URL (p_123456)
		$suchPers = "http://avedas-neu.zuv.uni-erlangen.de/converis/ws/public/infoobject/getrelated/Card/" . $ID . "/PERS_has_CARD";
		$this->xmlPers = simplexml_load_file($suchPers);
		$suchCard = "http://avedas-neu.zuv.uni-erlangen.de/converis/ws/public/infoobject/get/Card/". $ID;
		$this->xmlCard = simplexml_load_file($suchCard);
		$persID = $this->xmlPers->infoObject['id'];
		$suchAwards = "http://avedas-neu.zuv.uni-erlangen.de/converis/ws/public/infoobject/getrelated/Person/". $persID . "/awar_has_pers";
		$this->xmlAwards = simplexml_load_file($suchAwards);

		$person = $this->xmlPers->infoObject->attribute;
		foreach ($person as $attribut) {
			if ($attribut['language'] == 1) {
				$persAttribut = (string)$attribut['name'] . "_en";
			} else {
				$persAttribut = (string)$attribut['name'];
			}
			if ((string)$attribut['disposition'] == 'choicegroup' ) {
				$persDetail = (string)$attribut->additionalInfo;
			} else {
				$persDetail = (string)$attribut->data;
			}
			$this->persArray[$persAttribut] = $persDetail;
		}

		$card = $this->xmlCard->attribute;
		foreach ($card as $attribut) {
			if ($attribut['language'] == 1) {
				$cardAttribut = (string)$attribut['name'] . "_en";
			} else {
				$cardAttribut = (string)$attribut['name'];
			}
			if ((string)$attribut['disposition'] == 'choicegroup' ) {
				$cardDetail = (string)$attribut->additionalInfo;
			} else {
				$cardDetail = (string)$attribut->data;
			}
			$this->cardArray[$cardAttribut] = $cardDetail;
		}
	}

	/*
	 *  Persönliche Informationen, Kontakt etc.
	 */
	public function detail() {
		$vorname = strip_tags($this->cardArray['firstName']);
		$nachname = strip_tags($this->cardArray['lastName']);
		$academicTitle = strip_tags($this->persArray['Academic title']);
		$email = strip_tags($this->persArray['email']);
		$email2 = strip_tags($this->cardArray['email']);
		$phone = strip_tags($this->cardArray['phone']);
		$fax = strip_tags($this->cardArray['fax']);
		$website = strip_tags($this->cardArray['cfURI']);
		//$jobTitle = $this->cardArray['jobTitle'];
		$jobTitle = explode('&#32;-&#32;',substr(strip_tags($this->cardArray['allFunctions']), 0, -11));
		$jobTitle = $jobTitle[count($jobTitle)-1];

		echo "<h2>" . $academicTitle . " " . $vorname . " " . $nachname . "</h2>";
		echo ($jobTitle !='' ? "<p><strong>" . $jobTitle . "</strong><p>" : '');
		echo "<p>";
		echo "E-Mail (Person): " . ($email !='' ? $email : '');
		echo "<br />E-Mail (Card): " . ($email2 !='' ? $email2 : '');
		echo "<br />Telefon: " . ($phone !='' ? $phone : '');
		echo "<br />Fax: " . ($fax !='' ? $fax : '');
		echo "<br />Website: " . ($website !='' ? $website : '');
		echo "</p>";
	}

	/*
	 *  Auszeichnungen
	 */
	public function auszeichnungen() {
		$awards = $this->xmlAwards->infoObject;
		$awardArray = array();

		if(!empty($awards)) {
			echo "<h3>Auszeichnungen</h3>";
			echo "<ul>";

			foreach ($awards as $award) {
				$awardID = (string)$award['id'];

				foreach ($award as $attribut){
					if ($attribut['language'] == 1) {
						$awardAttribut = (string)$attribut['name'] . "_en";
					} else {
						$awardAttribut = (string)$attribut['name'];
					}
					if ((string)$attribut['disposition'] == 'choicegroup' ) {
						$awardDetail = (string)$attribut->additionalInfo;
					} else {
						$awardDetail = (string)$attribut->data;
					}
					$awardArray[$awardID][$awardAttribut] = $awardDetail;
				}

				$year = strip_tags($awardArray[$awardID]['Year award']);
				$awardType = strip_tags($awardArray[$awardID]['Type of award']);
				$awardName = strip_tags($awardArray[$awardID]['award_name']);
				$awardOrga = strip_tags($awardArray[$awardID]['award_organisation']);

				echo "<li>";
				echo $awardName . " (" . $year . ")";
				echo "<br />" . $awardOrga;
				echo "<br />(" . $awardType . ")";
				echo "</li>";
			}
			echo "</ul>";

		}

	}

}