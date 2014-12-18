<?php

require_once('class_cris.php');

class Mitarbeiterliste {

	public function __construct() {
		$getoptions = new CRIS();
		$options = $getoptions->options;
		$orgNr = $options['CRISOrgNr'];
		$this->pathPersonenseite = $options['Pfad_Personenseite'];
		$this->ignore = explode("|",$options['Ignoriere_Jobs']);
		$this->suchstring = 'http://avedas-neu.zuv.uni-erlangen.de/converis/ws/public/infoobject/getrelated/Organisation/' . $orgNr . '/CARD_has_ORGA';
		$this->mitarbeiter = simplexml_load_file($this->suchstring);

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
				$this->maArray[$this->maID][$maAttribut] = $maDetail;
			}
		}
		// Array alphabetisch sortieren
		$this->maArray = $this->record_sort($this->maArray);
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
			$jobs2 = explode('&#32;-&#32;',strip_tags(substr($mitarbeiter['allFunctions'], 0, -11)));
			$strJobs = $jobs2[count($jobs2)-1];
			if ($strJobs != '') {
				echo " (";
				echo $strJobs;
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
				if (($j == 'allFunctions') && $sub_element != '') {
					$jobs = explode('&#32;-&#32;',substr(strip_tags($sub_element), 0, -11));
					$job = $jobs[count($jobs)-1];
					$organigramm[$job][$i] = $element;
				} elseif (($j == 'allFunctions') && !$sub_element) {
					$organigramm['Andere'][$i]= $element;
				}
			}
		}

		// Mitarbeiter-Array nach Hierarchie sortieren
		$hierarchie = array(
			'Lehrstuhlinhaber/in',
			'Professurinhaber/in',
			'Juniorprofessor/in',
			'apl. Professor/in',
			'Privatdozent/in',
			'Honorarprofessor/in',
			'Emeritus / Emerita',
			'Professor/in im Ruhestand',
			'Gastprofessoren (h.b.) an einer Univ.',
			'Wissenschaftler/in',
			'Doktorand/in',
			'HiWi',
			'Verwaltungsmitarbeiter/in',
			'technische/r Mitarbeiter/in',
			'FoDa-Administrator/in',
			'Andere'
		);
		$organigramm = $this->sort_key($organigramm, $hierarchie);

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

	}

	/*
	 * Array sortieren (strings)
	 */

	private function record_sort($results) {

		// Define the custom sort function
		function custom_sort ($a, $b) { return (strcasecmp ($a['lastName'],$b['lastName']));}
		// Sort the multidimensional array
		uasort($results, "custom_sort");
		return $results;
	}

	private function sort_key(&$sort_array, $keys_array) {
		if(empty($sort_array) || !is_array($sort_array) || empty($keys_array)) return;
		if(!is_array($keys_array)) $keys_array = explode(',',$keys_array);
		if(!empty($keys_array)) $keys_array = array_reverse($keys_array);
		foreach($keys_array as $n){
			if(array_key_exists($n, $sort_array)){
				$newarray = array($n=>$sort_array[$n]); //copy the node before unsetting
				unset($sort_array[$n]); //remove the node
				$sort_array = $newarray + array_filter($sort_array); //combine copy with filtered array
			}
		}
		return $sort_array;
	}
}