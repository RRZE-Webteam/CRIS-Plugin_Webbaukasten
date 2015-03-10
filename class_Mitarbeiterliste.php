<?php

require_once('class_CRIS.php');
require_once("class_Tools.php");

class Mitarbeiterliste {

	public function __construct() {
		$getoptions = new CRIS();
		$options = $getoptions->options;
		$orgNr = $options['CRISOrgNr'];
		$this->pathPersonenseite = $options['Pfad_Personenseite'];
		$this->ignore = explode("|",$options['Ignoriere_Jobs']);
		$this->suchstring = 'https://cris.fau.de/ws-cached/public/infoobject/getrelated/Organisation/' . $orgNr . '/CARD_has_ORGA';
		$this->mitarbeiter = @simplexml_load_file($this->suchstring, 'SimpleXmlElement', LIBXML_NOERROR+LIBXML_NOWARNING);
		if (false === $this->mitarbeiter) {
			print "<p>'Keine Daten gefunden.</p>";
			return;
		}

		// XML -> Array
		$this->maArray = array();
		foreach ($this->mitarbeiter as $mitarbeiter) {
			$this->maID = (string)$mitarbeiter['id'];

			foreach ($mitarbeiter as $attribut){
				if ($attribut['language'] == 1) {
					$maAttribut = (string)$attribut['name'] . "_en";
				} else {
					$maAttribut = (string)$attribut['name'];
				}
				if ((string)$attribut['disposition'] == 'choicegroup' ) {
					$maDetail = (string)$attribut->additionalInfo;
				} else {
					$maDetail = (string)$attribut->data;
				}
				if($attribut['name'] == "allFunctions") {
					if (strstr($attribut->data, ' (')) {
						$functions = strstr($attribut->data, ' (', true);
					} else {
						$functions = $attribut->data;
					}
					$maDetail = explode(' - ', $functions);
				}
				$this->maArray[$this->maID][$maAttribut] = $maDetail;
			}
		}
		// Array alphabetisch sortieren
		$this->maArray = Tools::record_sortByName($this->maArray);
	}


	/*
	 * Alphabetisch sortierte Mitarbeiterliste
	 */
	public function liste() {
		echo "<ul>";
		foreach ($this->maArray as $maID=>$mitarbeiter) {
			echo "<li>";
			echo "<a href='" . $this->pathPersonenseite . "/" . $maID . "'>";
			echo strip_tags($mitarbeiter['firstName']) . " " . strip_tags($mitarbeiter['lastName']) . "</a>";
			if (!empty($mitarbeiter['allFunctions'])) {
				echo " (";
				echo implode(', ', $mitarbeiter['allFunctions']);
				echo ")";
			}
			echo "</li>";
		}
		echo "</ul>";
	}


	/*
	 * Nach Funktionen/jobTitle gegliederte Mitarbeiterliste
	 */
	public function organigramm() {

		// Mitarbeiter-Array umstrukturieren: Funktion -> ID -> Attribute -> Wert
		$organigramm = array();
		foreach($this->maArray as $i=>$element) {
			foreach($element as $j=>$sub_element) {
				if (($j == 'allFunctions') && !empty($sub_element)) {
					foreach ($sub_element as $job) {
						$organigramm[$job][$i] = $element;
					}
				} elseif (($j == 'allFunctions') && !$sub_element) {
					$organigramm['Andere'][$i]= $element;
				}
			}
		}

		// Mitarbeiter-Array nach Hierarchie sortieren
		$organigramm = Tools::sort_key($organigramm, Dicts::$jobOrder);

		// Organigramm ausgeben
		foreach ($organigramm as $i=>$funktion) {
			if (!in_array($i, $this->ignore)) {
				echo "<h3>" . $i . "</h3>";
				echo "<ul>";
				foreach ($funktion as $maID=>$mitarbeiter) {
					echo "<li>";
					//echo "<a href='/cris/person.shtml/" . $maID ."'>";
					echo "<a href='" . $this->pathPersonenseite . "/" . $maID . "'>";
					echo strip_tags($mitarbeiter['firstName']) . " " . strip_tags($mitarbeiter['lastName']) . "</a>";
					$jobs2 = explode('&#32;-&#32;',strip_tags(substr($mitarbeiter['allFunctions'], 0, -11)));
					echo "</li>";
				}
				echo "</ul>";
			}
		}
	} // Ende organigramm()

} // Ende class_Mitarbeiterliste