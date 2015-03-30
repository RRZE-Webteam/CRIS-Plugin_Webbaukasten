<?php

class Personendetail {

	public function __construct() {
		$getoptions = new CRIS();
		$this->options = $getoptions->options;

		libxml_use_internal_errors(true);
		$url = explode('/',$_SERVER['REQUEST_URI']);
		$this->ID = $url[count($url)-1]; //letztes Element der URL (p_123456)
		$suchPers = "https://cris.fau.de/ws-cached/public/infoobject/getrelated/Card/" . $this->ID . "/PERS_has_CARD";
		$this->xmlPers = Tools::XML2obj($suchPers);
		$suchCard = "https://cris.fau.de/ws-cached/public/infoobject/get/Card/". $this->ID;
		$this->xmlCard = Tools::XML2obj($suchCard);
		if (false === $this->xmlCard) {
			print "<p>" . __('Keine Daten gefunden.', 'fau-cris') . "</p>"
					."<p><a href=\"" . get_permalink() . "\">&rarr; " . __('Zur Mitarbeiterliste','fau-cris') . "</a></p>";
			return;
		}

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
		if (false === $this->xmlCard) {
			return;
		}
		$vorname = strip_tags($this->cardArray['firstName']);
		$nachname = strip_tags($this->cardArray['lastName']);
		$academicTitle = strip_tags($this->persArray['Academic title']);
		$email = strip_tags($this->persArray['email']);
		$phone = strip_tags($this->cardArray['phone']);
		$fax = strip_tags($this->cardArray['fax']);
		$website = strip_tags($this->cardArray['cfURI']);
		$jobTitle = $this->cardArray['allFunctions'];

		echo "<h2>"
			. ($academicTitle ? '<acronym title="' . Tools::getAcronym($academicTitle) . '">' . $academicTitle . "</acronym> " : '')
			. $vorname . " " . $nachname
			. "</h2>";
		echo ($jobTitle ? "<p><strong>" . $jobTitle . "</strong><p>" : '');
		echo "<p>";
		echo ($email ? "E-Mail: <a href=\"mailto:" . $email ."\">" . $email . '</a>' : '');
		echo ($phone ? "<br />Telefon: " . $phone : '');
		echo ($fax ? "<br />Fax: " . $fax : '');
		echo ($website ? "<br />Website: " . $website : '');
		echo "</p>";

		if (isset($this->options['Zeige_Publikationen']) && $this->options['Zeige_Publikationen'] == '1') {
			$liste = new Publikationsliste("person");
			$liste->pubNachJahr('klein');
		}

		// echo "<br /><p><a href=\"" . $this->options['Pfad_Personenseite'] . "\">&larr; Zur&uuml;ck zur Mitarbeiterliste</a></p>";

	}
}