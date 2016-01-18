<?php

require_once('class_CRIS.php');
require_once("class_Tools.php");


class Publikationen {

	private $pathPersonenseite;
	private $options;
	private $pubArray = array();

	public function __construct($einheit='', $id='') {
		$getoptions = new CRIS();
		$this->options = $getoptions->options;
		$orgNr = $this->options['CRISOrgNr'];
		$this->pathPersonenseite = $this->options['Pfad_Personenseite'];
		$this->pathPersonenseiteUnivis = $this->options['Pfad_Personenseite_Univis'];
		$this->pubOrder = $this->options['Reihenfolge_Publikationen'];
		$this->crisURL = "https://cris.fau.de/ws-cached/1.0/public/infoobject/";


		if ($einheit == "person") {
			$this->suchstring = $this->crisURL . 'getautorelated/Person/' . $id . '/PERS_2_PUBL_1';
		} elseif ($einheit == "orga") {
			// Publikationsliste für Organisationseinheit (überschreibt Orgeinheit aus Einstellungen!!!)
			$this->suchstring = $this->crisURL . 'getautorelated/Organisation/' . $id . '/ORGA_2_PUBL_1'; //142408
		} elseif ($einheit == "publication") {
			$this->suchstring = $this->crisURL . 'get/Publication/' . $id;
 		} else {
			// keine Einheit angegeben -> OrgNr aus Einstellungen verwenden
			$this->suchstring = $this->crisURL . "getautorelated/Organisation/" . $orgNr . "/ORGA_2_PUBL_1"; //142408
		}

		$xml = Tools::XML2obj($this->suchstring);
		$publications = $xml->infoObject;

		// XML -> Array

		$this->pubArray = array();

		foreach ($publications as $publication) {
			$pubID = (string)$publication['id'];

			foreach ($publication as $attribut){
				if ($attribut['language'] == 1) {
					$pubAttribut = (string)$attribut['name'] . "_en";
				} else {
					$pubAttribut = (string)$attribut['name'];
				}
				if ((string)$attribut['disposition'] == 'choicegroup' ) {
					$pubDetail = (string)$attribut->additionalInfo;
				} else {
					$pubDetail = (string)$attribut->data;
				}
				$this->pubArray[$pubID][$pubAttribut] = $pubDetail;
			}
		}
		//$this->pubArray = Tools::record_sortByYear($this->pubArray);

		// Mitarbeiter dieser Organisationseinheit (damit nur diese später verlinkt werden)
		$suchstringOrga = 'https://cris.fau.de/ws-cached/1.0/public/infoobject/getrelated/Organisation/' . $orgNr . '/CARD_has_ORGA';
		$xmlOrga = Tools::XML2obj($suchstringOrga);
		foreach ($xmlOrga as $card) {
			$this->inOrga[] = (string)$card['id'];
		}
	}


	/*
	 * Ausgabe aller Publikationen nach Jahren gegliedert
	 */

	public function pubNachJahr($year = '', $start = '', $type = '') {
		if (!isset($this->pubArray) || !is_array($this->pubArray)) return;

		$pubByYear = array();
		$output = '';

		// Publikationen filtern
		if ($year !='' || $start !='' || $type != '') {
			$publications = Tools::filter_publications($this->pubArray, $year, $start, $type);
		} else {
			$publications = $this->pubArray;
		}

		if (empty($publications)) {
			$output .= '<p>Es wurden leider keine Publikationen gefunden.</p>';
			return $output;
		}

		// Publikationen gliedern
		foreach ($publications as $i => $element) {
			foreach ($element as $j => $sub_element) {
				if (($j == 'publYear')) {
					$pubByYear[$sub_element][$i] = $element;
				}
			}
		}

		// Publikationen sortieren
		$keys = array_keys($pubByYear);
		rsort($keys);
		$pubByYear = Tools::sort_key($pubByYear, $keys);

		foreach ($pubByYear as $array_year => $publications) {
			if (empty($year)) {
				$output .= '<h3>' . $array_year . '</h3>';
			}
			// innerhalb des Publikationstyps alphabetisch nach Erstautor sortieren
			$publications = Tools::array_msort($publications, array('relAuthors' => SORT_ASC));
			$output .= $this->make_list($publications);
		}
		return $output;
	}

	/*
	 * Ausgabe aller Publikationen nach Publikationstypen gegliedert
	 */

	public function pubNachTyp($year = '', $start = '', $type = '') {
		if (!isset($this->pubArray) || !is_array($this->pubArray)) return;

		$pubByType = array();
		$output = '';

		// Publikationen filtern
		if ($year !='' || $start !='' || $type != '') {
			$publications = Tools::filter_publications($this->pubArray, $year, $start, $type);
		} else {
			$publications = $this->pubArray;
		}

		if (empty($publications)) {
			$output .= '<p>Es wurden leider keine Publikationen gefunden.</p>';
			return $output;
		}

		// Publikationen gliedern
		foreach ($publications as $i => $element) {
			foreach ($element as $j => $sub_element) {
				if (($j == 'Publication type')) {
					$pubByType[$sub_element][$i] = $element;
				}
			}
		}

		// Publikationstypen sortieren
		$order = explode("|", $this->pubOrder);
		if ($order[0] != ''  && array_key_exists($order[0],CRIS_Dicts::$pubNames)) {
			foreach ($order as $key => $value) {
				$order[$key] = Tools::getPubName($value, "en");
			}
			$pubByType = Tools::sort_key($pubByType, $order);
		} else {
			$pubByType = Tools::sort_key($pubByType, CRIS_Dicts::$pubOrder);
		}
		foreach ($pubByType as $array_type => $publications) {
			$title = Tools::getPubTranslation($array_type);

			// Zwischenüberschrift (= Publikationstyp), außer wenn nur ein Typ gefiltert wurde
			if (empty($type)) {
				$output .= "<h3>";
				$output .= $title;
				$output .= "</h3>";
			}

			// innerhalb des Publikationstyps nach Jahr abwärts sortieren
			$publications = Tools::array_msort($publications, array('publYear' => SORT_DESC));

			$output .= $this->make_list($publications);
		}
		return $output;
	} // Ende pubNachTyp()


	public function singlePub() {
		//print $id;
		$pubObject = Tools::XML2obj($this->suchstring);
		$this->publications = $pubObject->attribute;
		foreach ($this->publications as $attribut) {
			$this->pubID = (string) $pubObject['id'];
			if ($attribut['language'] == 1) {
				$pubAttribut = (string) $attribut['name'] . "_en";
			} else {
				$pubAttribut = (string) $attribut['name'];
			}
			if ((string) $attribut['disposition'] == 'choicegroup') {
				$pubDetail = (string) $attribut->additionalInfo;
			} else {
				$pubDetail = (string) $attribut->data;
			}
			$this->pubArray[$this->pubID][$pubAttribut] = $pubDetail;
		}

		/*echo "<pre>";
		//var_dump($pubObject['id']);
		var_dump($this->pubArray);
		echo "</pre>";*/

		if (!isset($this->pubArray) || !is_array($this->pubArray)) return;
		$output = $this->make_list($this->pubArray);
		return $output;
	}

	/* =========================================================================
	 * Private Functions
	 ======================================================================== */

	/*
	 * Ausgabe der Publikationsdetails, unterschiedlich nach Publikationstyp
	 */

	private function make_list($publications) {

		$output .= "<ul>";

		foreach ($publications as $id => $publication) {

			$authors = explode(", ", $publication['relAuthors']);
			$authorIDs = explode(",", $publication['relAuthorsId']);
			$authorsArray = array();
			foreach ($authorIDs as $i => $key) {
				$authorsArray[] = array('id' => $key, 'name' => $authors[$i]);
			}

			$pubDetails = array(
				'id' => $id,
				'authorsArray' => $authorsArray,
				'title' => (array_key_exists('cfTitle', $publication) ? strip_tags($publication['cfTitle']) : 'O.T.'),
				'city' => (array_key_exists('cfCityTown', $publication) ? strip_tags($publication['cfCityTown']) : 'O.O.'),
				'publisher' => (array_key_exists('publisher', $publication) ? strip_tags($publication['publisher']) : 'O.A.'),
				'year' => (array_key_exists('publYear', $publication) ? strip_tags($publication['publYear']) : 'O.J.'),
				'pubType' => (array_key_exists('Publication type', $publication) ? strip_tags($publication['Publication type']) : 'O.A.'),
				'pagesTotal' => (array_key_exists('cfTotalPages', $publication) ? strip_tags($publication['cfTotalPages']) : ''),
				'pagesRange' => (array_key_exists('pagesRange', $publication) ? strip_tags($publication['pagesRange']) : ''),
				'volume' => (array_key_exists('cfVol', $publication) ? strip_tags($publication['cfVol']) : 'O.A.'),
				'series' => (array_key_exists('cfSeries', $publication) ? strip_tags($publication['cfSeries']) : 'O.A.'),
				'seriesNumber' => (array_key_exists('cfNum', $publication) ? strip_tags($publication['cfNum']) : 'O.A.'),
				'ISBN' => (array_key_exists('cfISBN', $publication) ? strip_tags($publication['cfISBN']) : 'O.A.'),
				'ISSN' => (array_key_exists('cfISSN', $publication) ? strip_tags($publication['cfISSN']) : 'O.A.'),
				'DOI' => (array_key_exists('DOI', $publication) ? strip_tags($publication['DOI']) : 'O.A.'),
				'URI' => (array_key_exists('cfURI', $publication) ? strip_tags($publication['cfURI']) : 'O.A.'),
				'editiors' => (array_key_exists('Editor', $publication) ? strip_tags($publication['Editor']) : 'O.A.'),
				'booktitle' => (array_key_exists('Edited Volumes', $publication) ? strip_tags($publication['Edited Volumes']) : 'O.A.'), // Titel des Sammelbands
				'journaltitle' => (array_key_exists('journalName', $publication) ? strip_tags($publication['journalName']) : 'O.A.'),
				'conference' => (array_key_exists('Conference', $publication) ? strip_tags($publication['Conference']) : 'O.A.'),
				'origTitle' => (array_key_exists('Originaltitel', $publication) ? strip_tags($publication['Originaltitel']) : 'O.A.'),
				'origLanguage' => (array_key_exists('Language', $publication) ? strip_tags($publication['Language']) : 'O.A.')
			);

			$output .= "<li style='margin-bottom: 15px; line-height: 150%;'>";

			$authorList = array();
			foreach ($pubDetails['authorsArray'] as $author) {
				$span_pre = "<span class=\"author\">";
				$span_post = "</span>";
				$authordata = $span_pre . $author['name'] . $span_post;
				$author_firstname = explode(" ", $author['name'])[1];
				$author_lastname = explode(" ", $author['name'])[0];
				if ($author['id']
						&& !in_array($author['id'], array('invisible', 'external'))
						&& $this->options['Personeninfo_Univis']
						&& in_array($author['id'],$this->inOrga)) {
					$link_pre = "<a href=\"" . $this->pathPersonenseite . "/" . $author['id'] . "\">";
					$author['firstname']= explode(" ", $author['name'])[1];
					$author['lastname']= explode(" ", $author['name'])[0];
					$link_pre = "<a href=\"" . $this->pathPersonenseiteUnivis . "/" . $author['firstname'] . "-" .  $author['lastname'] . "\">";
					$link_post = "</a>";
					$authordata = $link_pre . $authordata . $link_post;
				}
				$authorList[] = $authordata;
			}
			$output .= implode(", ", $authorList);
			$output .= ($pubDetails['pubType'] == 'Editorial' ? ' (Hrsg.):' : ':');

			$output .= "<br /><span class=\"title\"><b>"
			. "<a href=\"https://cris.fau.de/converis/publicweb/Publication/" . $id
			. "\" target=\"blank\" title=\"Detailansicht in neuem Fenster &ouml;ffnen\">"
			. $pubDetails['title']
			. "</a>"
			. "</b></span>";


			switch ($pubDetails['pubType']) {

				case "Other": // Falling through
				case "Book":
					$output .= ((($pubDetails['city'] != '') || ($pubDetails['publisher'] != '') || ($pubDetails['year'] != '')) ? "<br />" : '');
					$output .= ($pubDetails['volume'] != '' ? $pubDetails['volume'] . ". " : '');
					$output .= ($pubDetails['city'] != '' ? "<span class=\"city\">" . $pubDetails['city'] . "</span>: " : '');
					$output .= ($pubDetails['publisher'] != '' ? $pubDetails['publisher'] . ", " : '');
					$output .= ($pubDetails['year'] != '' ? $pubDetails['year'] : '');
					$output .= ($pubDetails['series'] != '' ? "<br />" . $pubDetails['series'] : '');
					$output .= ($pubDetails['seriesNumber'] != '' ? "Bd. " . $pubDetails['seriesNumber'] : '');
					$output .= ($pubDetails['pagesTotal'] != '' ? "<br />" . $pubDetails['pagesTotal'] . " Seiten" : '');
					$output .= ($pubDetails['ISBN'] != '' ? "<br />ISBN: " . $pubDetails['ISBN'] : '');
					$output .= ($pubDetails['ISSN'] != '' ? "<br />ISSN: " . $pubDetails['ISSN'] : '');
					$output .= ($pubDetails['DOI'] != '' ? "<br />DOI: <a href='http://dx.doi.org/" . $pubDetails['DOI'] . "' target='blank'>" . $pubDetails['DOI'] . "</a>" : '');
					$output .= ($pubDetails['URI'] != '' ? "<br />URL: <a href='" . $pubDetails['URI'] . "' target='blank'>" . $pubDetails['URI'] . "</a>" : '');
					break;

				case "Article in Edited Volumes":
					$output .= ((($pubDetails['editiors'] != '') || ($pubDetails['booktitle'] != '')) ? "<br />" : '');
					$output .= ($pubDetails['editiors'] != '' ? "In: <strong>" . $pubDetails['editiors'] . '</strong> (Hrsg.):' : '');
					$output .= ($pubDetails['booktitle'] != '' ? " <strong><em>" . $pubDetails['booktitle'] . '</em></strong>' : '');
					$output .= ((($pubDetails['city'] != '') || ($pubDetails['publisher'] != '') || ($pubDetails['year'] != '')) ? "<br />" : '');
					$output .= ($pubDetails['volume'] != '' ? $pubDetails['volume'] . ". " : '');
					$output .= ($pubDetails['city'] != '' ? "<span class=\"city\">" . $pubDetails['city'] . "</span>: " : '');
					$output .= ($pubDetails['publisher'] != '' ? $pubDetails['publisher'] . ", " : '');
					$output .= ($pubDetails['year'] != '' ? $pubDetails['year'] : '');
					$output .= ($pubDetails['series'] != '' ? "<br />" . $pubDetails['series'] : '');
					$output .= ($pubDetails['seriesNumber'] != '' ? "Bd. " . $pubDetails['seriesNumber'] : '');
					$output .= ($pubDetails['pagesTotal'] != '' ? "<br />" . $pubDetails['pagesTotal'] . " Seiten" : '');
					$output .= ($pubDetails['ISBN'] != '' ? "<br />ISBN: " . $pubDetails['ISBN'] : '');
					$output .= ($pubDetails['ISSN'] != '' ? "<br />ISSN: " . $pubDetails['ISSN'] : '');
					$output .= ($pubDetails['DOI'] != '' ? "<br />DOI: <a href='http://dx.doi.org/" . $pubDetails['DOI'] . "' target='blank'>" . $pubDetails['DOI'] . "</a>" : '');
					$output .= ($pubDetails['URI'] != '' ? "<br />URL: <a href='" . $pubDetails['URI'] . "' target='blank'>" . $pubDetails['URI'] . "</a>" : '');
					break;

				case "Journal article":
					$output .= ((($pubDetails['journaltitle'] != '') || ($pubDetails['volume'] != '') || ($pubDetails['year'] != '') || ($pubDetails['pagesRange'] != '')) ? "<br />" : '');
					$output .= ($pubDetails['journaltitle'] != '' ? "In: <strong>" . $pubDetails['journaltitle'] . '</strong> ' : '');
					$output .= ($pubDetails['volume'] != '' ? $pubDetails['volume'] . ". " : '');
					$output .= ($pubDetails['year'] != '' ? " (" . $pubDetails['year'] . ")" : '');
					$output .= ($pubDetails['pagesRange'] != '' ? ", S. " . $pubDetails['pagesRange'] : '');
					$output .= ($pubDetails['DOI'] != '' ? "<br />DOI: <a href='http://dx.doi.org/" . $pubDetails['DOI'] . "' target='blank'>" . $pubDetails['DOI'] . "</a>" : '');
					$output .= ($pubDetails['URI'] != '' ? "<br />URL: <a href='" . $pubDetails['URI'] . "' target='blank'>" . $pubDetails['URI'] . "</a>" : '');
					break;

				case "Conference contribution":
					$output .= ((($pubDetails['conference'] != '') || ($pubDetails['publisher'] != '')) ? "<br />" : '');
					$output .= ($pubDetails['conference'] != '' ? $pubDetails['conference'] : '');
					$output .= ((($pubDetails['conference'] != '') && ($pubDetails['publisher'] != '')) ? ", " : '');
					$output .= ($pubDetails['publisher'] != '' ? $pubDetails['publisher'] : '');
					$output .= ((($pubDetails['city'] != '') || ($pubDetails['year'] != '')) ? "<br />" : '');
					$output .= ($pubDetails['city'] != '' ? "<span class=\"city\">" . $pubDetails['city'] . "</span>" : '');
					$output .= ($pubDetails['year'] != '' ? " (" . $pubDetails['year'] . ")" : '');
					$output .= ($pubDetails['DOI'] != '' ? "<br />DOI: <a href='http://dx.doi.org/" . $pubDetails['DOI'] . "' target='blank'>" . $pubDetails['DOI'] . "</a>" : '');
					$output .= ($pubDetails['URI'] != '' ? "<br />URL: <a href='" . $pubDetails['URI'] . "' target='blank'>" . $pubDetails['URI'] . "</a>" : '');
					break;
				case "Editorial":
					$output .= ((($pubDetails['city'] != '') || ($pubDetails['publisher'] != '') || ($pubDetails['year'] != '')) ? "<br />" : '');
					$output .= ($pubDetails['volume'] != '' ? $pubDetails['volume'] . ". " : '');
					$output .= ($pubDetails['city'] != '' ? "<span class=\"city\">" . $pubDetails['city'] . "</span>: " : '');
					$output .= ($pubDetails['publisher'] != '' ? $pubDetails['publisher'] . ", " : '');
					$output .= ($pubDetails['year'] != '' ? $pubDetails['year'] : '');
					$output .= ($pubDetails['series'] != '' ? "<br />" . $pubDetails['series'] : '');
					$output .= ($pubDetails['seriesNumber'] != '' ? "Bd. " . $pubDetails['seriesNumber'] : '');
					$output .= ($pubDetails['pagesTotal'] != '' ? "<br />" . $pubDetails['pagesTotal'] . " Seiten" : '');
					$output .= ($pubDetails['ISBN'] != '' ? "<br />ISBN: " . $pubDetails['ISBN'] : '');
					$output .= ($pubDetails['ISSN'] != '' ? "<br />ISSN: " . $pubDetails['ISSN'] : '');
					$output .= ($pubDetails['DOI'] != '' ? "<br />DOI: <a href='http://dx.doi.org/" . $pubDetails['DOI'] . "' target='blank'>" . $pubDetails['DOI'] . "</a>" : '');
					$output .= ($pubDetails['URI'] != '' ? "<br />URL: <a href='" . $pubDetails['URI'] . "' target='blank'>" . $pubDetails['URI'] . "</a>" : '');
					break;
				case "Thesis":
					$output .= "<br />Abschlussarbeit";
					$output .= ($pubDetails['DOI'] != '' ? "<br />DOI: <a href='http://dx.doi.org/" . $pubDetails['DOI'] . "' target='blank'>" . $pubDetails['DOI'] . "</a>" : '');
					$output .= ($pubDetails['URI'] != '' ? "<br />URL: <a href='" . $pubDetails['URI'] . "' target='blank'>" . $pubDetails['URI'] . "</a>" : '');
					break;
				case "Translation":
					$output .= ((($pubDetails['city'] != '') || ($pubDetails['publisher'] != '') || ($pubDetails['year'] != '')) ? "<br />" : '');
					$output .= ($pubDetails['volume'] != '' ? $pubDetails['volume'] . ". " : '');
					$output .= ($pubDetails['city'] != '' ? "<span class=\"city\">" . $pubDetails['city'] . "</span>: " : '');
					$output .= ($pubDetails['publisher'] != '' ? $pubDetails['publisher'] . ", " : '');
					$output .= ($pubDetails['series'] != '' ? "<br />" . $pubDetails['series'] : '');
					$output .= ($pubDetails['seriesNumber'] != '' ? "Bd. " . $pubDetails['seriesNumber'] : '');
					$output .= ($pubDetails['pagesTotal'] != '' ? "<br />" . $pubDetails['pagesTotal'] . " Seiten" : '');
					$output .= ($pubDetails['ISBN'] != '' ? "<br />ISBN: " . $pubDetails['ISBN'] : '');
					$output .= ($pubDetails['ISSN'] != '' ? "<br />ISSN: " . $pubDetails['ISSN'] : '');
					$output .= ($pubDetails['DOI'] != '' ? "<br />DOI: <a href='http://dx.doi.org/" . $pubDetails['DOI'] . "' target='blank'>" . $pubDetails['DOI'] . "</a>" : '');
					$output .= ($pubDetails['URI'] != '' ? "<br />URL: <a href='" . $pubDetails['URI'] . "' target='blank'>" . $pubDetails['URI'] . "</a>" : '');
					$output .= ($pubDetails['origTitle'] != '' ? "<br />Originaltitel: " . $pubDetails['origTitle'] : '');
					$output .= ($pubDetails['origLanguage'] != '' ? "<br />Originalsprache: " . $pubDetails['origLanguage'] : '');
					break;
			}
			$output .= "</li>";
		}
		$output .= "</ul>";

		return $output;
	}
}