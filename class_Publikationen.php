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
		$this->suchstring = '';

        if((!$orgNr||$orgNr==0) && $id=='') {
            print '<p><strong>' . __('Bitte geben Sie die CRIS-ID der Organisation, Person oder Publikation an.','fau-cris') . '</strong></p>';
			return;
        }

		if ($einheit == "person") {
			// Publikationsliste für einzelne Person
			$this->suchstring = $this->crisURL .'getautorelated/Person/' . $id . '/PERS_2_PUBL_1';
		} elseif ($einheit == "orga") {
			// Publikationsliste für Organisationseinheit (überschreibt Orgeinheit aus Einstellungen!!!)
			$this->suchstring = $this->crisURL ."getautorelated/Organisation/" . $id . "/ORGA_2_PUBL_1"; //142408
		} elseif ($einheit == "publication") {
			$this->suchstring = $this->crisURL . 'get/Publication/' . $id;
 		} else {
			// keine Einheit angegeben -> OrgNr aus Einstellungen verwenden
			$this->suchstring = $this->crisURL . "getautorelated/Organisation/" . $orgNr . "/ORGA_2_PUBL_1"; //142408
		}

		$xml = Tools::XML2obj($this->suchstring);

		if (!$xml) {
			return;
		}

		$this->publications = $xml->infoObject;

		// XML -> Array

		$this->pubArray = array();

		foreach ($this->publications as $publication) {
			$this->pubID = (string) $publication['id'];

			foreach ($publication as $attribut) {
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

	public function pubNachJahr($year = '', $start = '', $type = '', $quotation = '', $items = '') {
		if (!isset($this->pubArray) || !is_array($this->pubArray)) return;

		$pubByYear = array();
		$output = '';

		// Publikationen filtern
		if ($year !='' || $start !='' || $type != '') {
			$publications = Tools::filter_publications($this->pubArray, $year, $start, $type);
		} else {
			$publications = $this->pubArray;
		}

		if ($items != '') {
			//print $items;
			$first = (integer) explode('-', $items)[0];	// 1
			$last = (integer) explode('-', $items)[1];	// 10
			$offset = $first - 1;	// 0
			$length = $last - $offset;	//10
			$publications = array_slice($publications, $offset, $length, true);
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
			//$publications = Tools::record_sortByVirtualdate($publications);
			$publications = Tools::array_msort($publications, array('virtualdate' => SORT_DESC));
			//$publications = Tools::array_msort($publications, array('relAuthors' => SORT_ASC));
			if ($quotation == 'apa' || $quotation == 'mla') {
				$output .= $this->make_quotation_list($publications, $quotation);
			} else {
				$output .= $this->make_list($publications);
			}
		}
		return $output;
	}

	/*
	 * Ausgabe aller Publikationen nach Publikationstypen gegliedert
	 */

	public function pubNachTyp($year = '', $start = '', $type = '', $quotation = '') {
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

			$title = Tools::getpubTitle($array_type, get_locale());

			// Zwischenüberschrift (= Publikationstyp), außer wenn nur ein Typ gefiltert wurde
			if (empty($type)) {
				$output .= "<h3>";
				$output .= $title;
				$output .= "</h3>";
			}

			// innerhalb des Publikationstyps nach Jahr abwärts sortieren
			$publications = Tools::array_msort($publications, array('publYear' => SORT_DESC));

			if ($quotation == 'apa' || $quotation == 'mla') {
				$output .= $this->make_quotation_list($publications, $quotation);
			} else {
				$output .= $this->make_list($publications);
			}
		}
		return $output;
	} // Ende pubNachTyp()

	public function singlePub($quotation = '') {
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

		if (!isset($this->pubArray) || !is_array($this->pubArray)) return;

		if ($quotation == 'apa' || $quotation == 'mla') {
			$output = $this->make_quotation_list($this->pubArray, $quotation);
		} else {
			$output = $this->make_list($this->pubArray);
		}

		return $output;
	}


	/* =========================================================================
	 * Private Functions
	 ======================================================================== */

	/*
	 * Ausgabe der Publikationsdetails in Zitierweise (MLA/APA)
	 */

	private function make_quotation_list($publications, $quotation) {

		$quotation = strtoupper($quotation);
		$publist = "<ul class=\"cris-publications\">";

		foreach ($publications as $publication) {
			$publist .= "<li>";
			$publist .= $publication['quotation' . $quotation];
			$publist .= "</li>";
		}

		$publist .= "</ul>";

		return $publist;
	}


	/*
	 * Ausgabe der Publikationsdetails, unterschiedlich nach Publikationstyp
	 */

	private function make_list($publications) {

		$publist = "<ul class=\"cris-publications\">";

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
				'virtualdate' => (array_key_exists('virtualdate', $publication) ? strip_tags($publication['virtualdate']) : 'X'),
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

			$publist .= "<li>";

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
			//$publist .= $pubDetails['virtualdate'] . "<br />";
			$publist .= implode(", ", $authorList);
			$publist .= ($pubDetails['pubType'] == 'Editorial' ? ' (Hrsg.):' : ':');

			$publist .= "<br /><span class=\"title\"><b>"
			. "<a href=\"https://cris.fau.de/converis/publicweb/Publication/" . $id
			. "\" target=\"blank\" title=\"Detailansicht in neuem Fenster &ouml;ffnen\">"
			. $pubDetails['title']
			. "</a>"
			. "</b></span>";


			switch ($pubDetails['pubType']) {

				case "Other": // Falling through
				case "Book":
					$publist .= ((($pubDetails['city'] != '') || ($pubDetails['publisher'] != '') || ($pubDetails['year'] != '')) ? "<br />" : '');
					$publist .= ($pubDetails['volume'] != '' ? $pubDetails['volume'] . ". " : '');
					$publist .= ($pubDetails['city'] != '' ? "<span class=\"city\">" . $pubDetails['city'] . "</span>: " : '');
					$publist .= ($pubDetails['publisher'] != '' ? $pubDetails['publisher'] . ", " : '');
					$publist .= ($pubDetails['year'] != '' ? $pubDetails['year'] : '');
					$publist .= ($pubDetails['series'] != '' ? "<br />" . $pubDetails['series'] : '');
					$publist .= ($pubDetails['seriesNumber'] != '' ? "Bd. " . $pubDetails['seriesNumber'] : '');
					$publist .= ($pubDetails['pagesTotal'] != '' ? "<br />" . $pubDetails['pagesTotal'] . " " . __('Seiten','fau-cris') : '');
					$publist .= ($pubDetails['ISBN'] != '' ? "<br />ISBN: " . $pubDetails['ISBN'] : '');
					$publist .= ($pubDetails['ISSN'] != '' ? "<br />ISSN: " . $pubDetails['ISSN'] : '');
					$publist .= ($pubDetails['DOI'] != '' ? "<br />DOI: <a href='http://dx.doi.org/" . $pubDetails['DOI'] . "' target='blank'>" . $pubDetails['DOI'] . "</a>" : '');
					$publist .= ($pubDetails['URI'] != '' ? "<br />URL: <a href='" . $pubDetails['URI'] . "' target='blank'>" . $pubDetails['URI'] . "</a>" : '');
					break;

				case "Article in Edited Volumes":
					$publist .= ((($pubDetails['editiors'] != '') || ($pubDetails['booktitle'] != '')) ? "<br />" : '');
					$publist .= ($pubDetails['editiors'] != '' ? "In: <strong>" . $pubDetails['editiors'] . '</strong> (Hrsg.):' : '');
					$publist .= ($pubDetails['booktitle'] != '' ? " <strong><em>" . $pubDetails['booktitle'] . '</em></strong>' : '');
					$publist .= ((($pubDetails['city'] != '') || ($pubDetails['publisher'] != '') || ($pubDetails['year'] != '')) ? "<br />" : '');
					$publist .= ($pubDetails['volume'] != '' ? $pubDetails['volume'] . ". " : '');
					$publist .= ($pubDetails['city'] != '' ? "<span class=\"city\">" . $pubDetails['city'] . "</span>: " : '');
					$publist .= ($pubDetails['publisher'] != '' ? $pubDetails['publisher'] . ", " : '');
					$publist .= ($pubDetails['year'] != '' ? $pubDetails['year'] : '');
					$publist .= ($pubDetails['series'] != '' ? "<br />" . $pubDetails['series'] : '');
					$publist .= ($pubDetails['seriesNumber'] != '' ? "Bd. " . $pubDetails['seriesNumber'] : '');
					$publist .= ($pubDetails['pagesTotal'] != '' ? "<br />" . $pubDetails['pagesTotal'] . " " . __('Seiten','fau-cris') : '');
					$publist .= ($pubDetails['ISBN'] != '' ? "<br />ISBN: " . $pubDetails['ISBN'] : '');
					$publist .= ($pubDetails['ISSN'] != '' ? "<br />ISSN: " . $pubDetails['ISSN'] : '');
					$publist .= ($pubDetails['DOI'] != '' ? "<br />DOI: <a href='http://dx.doi.org/" . $pubDetails['DOI'] . "' target='blank'>" . $pubDetails['DOI'] . "</a>" : '');
					$publist .= ($pubDetails['URI'] != '' ? "<br />URL: <a href='" . $pubDetails['URI'] . "' target='blank'>" . $pubDetails['URI'] . "</a>" : '');
					break;

				case "Journal article":
					$publist .= ((($pubDetails['journaltitle'] != '') || ($pubDetails['volume'] != '') || ($pubDetails['year'] != '') || ($pubDetails['pagesRange'] != '')) ? "<br />" : '');
					$publist .= ($pubDetails['journaltitle'] != '' ? "In: <strong>" . $pubDetails['journaltitle'] . '</strong> ' : '');
					$publist .= ($pubDetails['volume'] != '' ? $pubDetails['volume'] . ". " : '');
					$publist .= ($pubDetails['year'] != '' ? " (" . $pubDetails['year'] . ")" : '');
					$publist .= ($pubDetails['pagesRange'] != '' ? ", S. " . $pubDetails['pagesRange'] : '');
					$publist .= ($pubDetails['DOI'] != '' ? "<br />DOI: <a href='http://dx.doi.org/" . $pubDetails['DOI'] . "' target='blank'>" . $pubDetails['DOI'] . "</a>" : '');
					$publist .= ($pubDetails['URI'] != '' ? "<br />URL: <a href='" . $pubDetails['URI'] . "' target='blank'>" . $pubDetails['URI'] . "</a>" : '');
					break;

				case "Conference contribution":
					$publist .= ((($pubDetails['conference'] != '') || ($pubDetails['publisher'] != '')) ? "<br />" : '');
					$publist .= ($pubDetails['conference'] != '' ? $pubDetails['conference'] : '');
					$publist .= ((($pubDetails['conference'] != '') && ($pubDetails['publisher'] != '')) ? ", " : '');
					$publist .= ($pubDetails['publisher'] != '' ? $pubDetails['publisher'] : '');
					$publist .= ((($pubDetails['city'] != '') || ($pubDetails['year'] != '')) ? "<br />" : '');
					$publist .= ($pubDetails['city'] != '' ? "<span class=\"city\">" . $pubDetails['city'] . "</span>" : '');
					$publist .= ($pubDetails['year'] != '' ? " (" . $pubDetails['year'] . ")" : '');
					$publist .= ($pubDetails['DOI'] != '' ? "<br />DOI: <a href='http://dx.doi.org/" . $pubDetails['DOI'] . "' target='blank'>" . $pubDetails['DOI'] . "</a>" : '');
					$publist .= ($pubDetails['URI'] != '' ? "<br />URL: <a href='" . $pubDetails['URI'] . "' target='blank'>" . $pubDetails['URI'] . "</a>" : '');
					break;
				case "Editorial":
					$publist .= ((($pubDetails['city'] != '') || ($pubDetails['publisher'] != '') || ($pubDetails['year'] != '')) ? "<br />" : '');
					$publist .= ($pubDetails['volume'] != '' ? $pubDetails['volume'] . ". " : '');
					$publist .= ($pubDetails['city'] != '' ? "<span class=\"city\">" . $pubDetails['city'] . "</span>: " : '');
					$publist .= ($pubDetails['publisher'] != '' ? $pubDetails['publisher'] . ", " : '');
					$publist .= ($pubDetails['year'] != '' ? $pubDetails['year'] : '');
					$publist .= ($pubDetails['series'] != '' ? "<br />" . $pubDetails['series'] : '');
					$publist .= ($pubDetails['seriesNumber'] != '' ? "Bd. " . $pubDetails['seriesNumber'] : '');
					$publist .= ($pubDetails['pagesTotal'] != '' ? "<br />" . $pubDetails['pagesTotal'] . " Seiten" : '');
					$publist .= ($pubDetails['ISBN'] != '' ? "<br />ISBN: " . $pubDetails['ISBN'] : '');
					$publist .= ($pubDetails['ISSN'] != '' ? "<br />ISSN: " . $pubDetails['ISSN'] : '');
					$publist .= ($pubDetails['DOI'] != '' ? "<br />DOI: <a href='http://dx.doi.org/" . $pubDetails['DOI'] . "' target='blank'>" . $pubDetails['DOI'] . "</a>" : '');
					$publist .= ($pubDetails['URI'] != '' ? "<br />URL: <a href='" . $pubDetails['URI'] . "' target='blank'>" . $pubDetails['URI'] . "</a>" : '');
					break;
				case "Thesis":
					$publist .= "<br />Abschlussarbeit";
					$publist .= ($pubDetails['DOI'] != '' ? "<br />DOI: <a href='http://dx.doi.org/" . $pubDetails['DOI'] . "' target='blank'>" . $pubDetails['DOI'] . "</a>" : '');
					$publist .= ($pubDetails['URI'] != '' ? "<br />URL: <a href='" . $pubDetails['URI'] . "' target='blank'>" . $pubDetails['URI'] . "</a>" : '');
					break;
				case "Translation":
					$publist .= ((($pubDetails['city'] != '') || ($pubDetails['publisher'] != '') || ($pubDetails['year'] != '')) ? "<br />" : '');
					$publist .= ($pubDetails['volume'] != '' ? $pubDetails['volume'] . ". " : '');
					$publist .= ($pubDetails['city'] != '' ? "<span class=\"city\">" . $pubDetails['city'] . "</span>: " : '');
					$publist .= ($pubDetails['publisher'] != '' ? $pubDetails['publisher'] . ", " : '');
					$publist .= ($pubDetails['series'] != '' ? "<br />" . $pubDetails['series'] : '');
					$publist .= ($pubDetails['seriesNumber'] != '' ? "Bd. " . $pubDetails['seriesNumber'] : '');
					$publist .= ($pubDetails['pagesTotal'] != '' ? "<br />" . $pubDetails['pagesTotal'] . " Seiten" : '');
					$publist .= ($pubDetails['ISBN'] != '' ? "<br />ISBN: " . $pubDetails['ISBN'] : '');
					$publist .= ($pubDetails['ISSN'] != '' ? "<br />ISSN: " . $pubDetails['ISSN'] : '');
					$publist .= ($pubDetails['DOI'] != '' ? "<br />DOI: <a href='http://dx.doi.org/" . $pubDetails['DOI'] . "' target='blank'>" . $pubDetails['DOI'] . "</a>" : '');
					$publist .= ($pubDetails['URI'] != '' ? "<br />URL: <a href='" . $pubDetails['URI'] . "' target='blank'>" . $pubDetails['URI'] . "</a>" : '');
					$publist .= ($pubDetails['origTitle'] != '' ? "<br />Originaltitel: " . $pubDetails['origTitle'] : '');
					$publist .= ($pubDetails['origLanguage'] != '' ? "<br />Originalsprache: " . $pubDetails['origLanguage'] : '');
					break;
			}
			$publist .= "</li>";
		}
		$publist .= "</ul>";

		return $publist;
	}

}