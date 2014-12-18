<?php

require_once('class_cris.php');

class Publikationsliste {

	public function __construct($einheit) {
		$getoptions = new CRIS();
		$options = $getoptions->options;
		$orgNr = $options['CRISOrgNr'];
		$this->pathPersonenseite = $options['Pfad_Personenseite'];

		switch ($einheit) {
			case "person" : //Publikationsliste nach Card (für Personendetailseite)
				$this->url = explode('/',$_SERVER['REQUEST_URI']);
				$this->ID = $this->url[count($this->url)-1]; //letztes Element der URL (161182)
				$this->suchstring = 'http://avedas-neu.zuv.uni-erlangen.de/converis/ws/public/infoobject/getrelated/Card/' . $this->ID . '/Publ_has_CARD';
				break;
			default: // keine Einheit angegeben -> OrgNr verwenden
				$this->suchstring = "http://avedas-neu.zuv.uni-erlangen.de/converis/ws/public/infoobject/getautorelated/Organisation/" . $orgNr . "/ORGA_2_PUBL_1"; //141440
		}

		$this->xml = simplexml_load_file($this->suchstring);
		$this->publications = $this->xml->infoObject;

		// XML -> Array

		$this->pubArray = array();

		foreach ($this->publications as $publication) {
			$this->pubID = (string)$publication['id'];

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
				$this->pubArray[$this->pubID][$pubAttribut] = $pubDetail;
			}
		}
		$this->pubArray = $this->record_sort($this->pubArray);

	}

	/*
	 * Ausgabe aller Publikationen nach Jahren gegliedert
	 */
	public function pubNachJahr() {

		if(empty($this->publications)) {
			echo "<p>Es wurden leider keine Publikationen gefunden.</p>";
		}

		$pubByYear = array();

		foreach($this->pubArray as $i=>$element) {
			foreach($element as $j=>$sub_element) {
				if (($j == 'publYear') ) {
					$pubByYear[$sub_element][$i]= $element;
				}
			}
		}

		echo "<ul>";

		foreach ($pubByYear as $year=>$publications) {

			echo "<h3>" . $year . "</h3>";
			echo "<ul>";

			foreach ($publications as $publication) {
				$authors = explode(", ", $publication['relAuthors']);
				$authorIDs = explode(",", $publication['relAuthorsId']);
				$authorsArray = array();
				foreach ($authorIDs as $i => $key) {
					$authorsArray[] = array($key => $authors[$i]);
				}

				$pubDetails = array(
					authorsArray => $authorsArray,
					title => strip_tags($publication['cfTitle']),
					city => strip_tags($publication['cfCityTown']),
					publisher => strip_tags($publication['publisher']),
					year => strip_tags($publication['publYear']),
					pubType => strip_tags($publication['Publication type']),
					pagesTotal => strip_tags($publication['cfTotalPages']),
					pagesRange => strip_tags($publication['pagesRange']),
					volume => strip_tags($publication['cfVol']),
					series => strip_tags($publication['cfSeries']),
					seriesNumber => strip_tags($publication['cfNum']),
					ISBN => strip_tags($publication['cfISBN']),
					ISSN => strip_tags($publication['cfISSN']),
					DOI => strip_tags($publication['DOI']),
					URI => strip_tags($publication['cfURI']),
					editiors => strip_tags($publication['Editor']),
					booktitle => strip_tags($publication['Edited Volumes']), // Titel des Sammelbands
					journaltitle => strip_tags($publication['journalName']),
					conference => strip_tags($publication['Conference']),
					origTitle => strip_tags($publication['Originaltitel']),
					origLanguage => strip_tags($publication['Language'])
				);

				echo "<li style='margin-bottom: 15px; line-height: 150%;'>";
				foreach ($pubDetails['authorsArray'] as $author) {
					foreach ($author as $authorID=>$authorName) {
						if ($authorID && $authorID != 'external') {
							echo "<a href='" . $this->pathPersonenseite . "/" . substr($authorID, 0, -2) . "'>";
							//echo "<a href='/cris/person.shtml/" . substr($authorID, 0, -2) . "'>";
						}
						echo "<span class=\"author\">" . $authorName . "</span>";
						if ($authorID && $authorID != 'external') {
							echo "</a>";
						}
						echo ", ";
					}
				}
				echo ":";
				echo "<br /><span class=\"title\"><b>" . $pubDetails['title'] . "</b></span>";

				$this->echo_list($pubDetails);

				echo "</li>";
			}
			echo "</ul>";


		}
	}

	/*
	 * Ausgabe aller Publikationen nach Publikationstypen gegliedert
	 */
	public function pubNachTyp() {

		if(empty($this->publications)) {
			echo "<p>Es wurden leider keine Publikationen gefunden.</p>";
		}

		$pubByType = array();

		foreach($this->pubArray as $i=>$element) {
			foreach($element as $j=>$sub_element) {
				if (($j == 'Publication type') ) {
					$pubByType[$sub_element][$i]= $element;
				}
			}
		}
		/*echo "<pre>";
		//print_r($this->pubArray);
		print_r($pubByYear);
		echo "</pre>";*/

		echo "<ul>";

		foreach ($pubByType as $type=>$publications) {

			echo "<h3>";
			switch ($type) {
				case 'Book':
					echo "B&uuml;cher";
					break;
				case 'Journal article':
					echo "Zeitschriftenartikel";
					break;
				case 'Conference contribution':
					echo "Konferenzbeitr&auml;ge";
					break;
				case 'Article in Edited Volumes':
					echo "Beitr&auml;ge in Sammelb&auml;nden";
					break;
				case 'Editorial':
					echo "Herausgeberschaften";
					break;
				case 'Thesis':
					echo "Abschlussarbeiten";
					break;
				case 'Translation':
					echo "&Uuml;bersetzungen";
					break;
				case 'Other':
					echo "Andere";
					break;
				default:
					echo $type;
			}
			echo "</h3>";
			echo "<ul>";

			foreach ($publications as $publication) {

				$authors = explode(", ", $publication['relAuthors']);
				$authorIDs = explode(",", $publication['relAuthorsId']);
				$authorsArray = array();
				foreach ($authorIDs as $i => $key) {
					$authorsArray[] = array($key => $authors[$i]);
				}

				$pubDetails = array(
					authorsArray => $authorsArray,
					title => $publication['cfTitle'],
					city => $publication['cfCityTown'],
					publisher => $publication['publisher'],
					year => $publication['publYear'],
					pubType => $publication['Publication type'],
					pagesTotal => $publication['cfTotalPages'],
					pagesRange => $publication['pagesRange'],
					volume => $publication['cfVol'],
					series => $publication['cfSeries'],
					seriesNumber => $publication['cfNum'],
					ISBN => $publication['cfISBN'],
					ISSN => $publication['cfISSN'],
					DOI => $publication['DOI'],
					URI => $publication['cfURI'],
					editiors => $publication['Editor'],
					booktitle => $publication['Edited Volumes'], // Titel des Sammelbands
					journaltitle => $publication['journalName'],
					conference => $publication['Conference'],
					origTitle => $publication['Originaltitel'],
					origLanguage => $publication['Language']
				);

				echo "<li style='margin-bottom: 15px; line-height: 150%;'>";
				foreach ($pubDetails['authorsArray'] as $author) {
					foreach ($author as $authorID=>$authorName) {
						if ($authorID && $authorID != 'external') {
							echo "<a href='" . $this->pathPersonenseite . "/" . substr($authorID, 0, -2) . "'>";
							//echo "<a href='/cris/person.shtml/" . substr($authorID, 0, -2) . "'>";

						}
						echo "<span class=\"author\">" . $authorName . "</span>";
						if ($authorID && $authorID != 'external') {
							echo "</a>";
						}
						echo ", ";
					}
				}
				echo ":";
				echo "<br /><span class=\"title\"><b>" . $pubDetails['title'] . "</b></span>";

				$this->echo_list($pubDetails);

				echo "</li>";
			}
			echo "</ul>";
		}
	}


	/*
	 * Ausgabe einzelner Publikationstypen
	 */
	public function publikationstypen($typ) {

		if(empty($this->publications)) {
			echo "<p>Es wurden leider keine Publikationen gefunden.</p>";
		}

		echo "<ul>";

		foreach ($this->pubArray as $publication) {

			$authors = explode(", ", $publication['relAuthors']);
			$authorIDs = explode(",", $publication['relAuthorsId']);
			$authorsArray = array();
			foreach ($authorIDs as $i => $key) {
				$authorsArray[] = array($key => $authors[$i]);
			}

			$pubDetails = array(
				authorsArray => $authorsArray,
				title => strip_tags($publication['cfTitle']),
				city => strip_tags($publication['cfCityTown']),
				publisher => strip_tags($publication['publisher']),
				year => strip_tags($publication['publYear']),
				pubType => strip_tags($publication['Publication type']),
				pagesTotal => strip_tags($publication['cfTotalPages']),
				pagesRange => strip_tags($publication['pagesRange']),
				volume => strip_tags($publication['cfVol']),
				series => strip_tags($publication['cfSeries']),
				seriesNumber => strip_tags($publication['cfNum']),
				ISBN => strip_tags($publication['cfISBN']),
				ISSN => strip_tags($publication['cfISSN']),
				DOI => strip_tags($publication['DOI']),
				URI => strip_tags($publication['cfURI']),
				editiors => strip_tags($publication['Editor']),
				booktitle => strip_tags($publication['Edited Volumes']), // Titel des Sammelbands
				journaltitle => strip_tags($publication['journalName']),
				conference => strip_tags($publication['Conference']),
				origTitle => strip_tags($publication['Originaltitel']),
				origLanguage => strip_tags($publication['Language'])
			);

			if ($pubDetails['pubType'] == $typ) {
				echo "<li style='margin-bottom: 15px; line-height: 150%;'>";
				foreach ($pubDetails['authorsArray'] as $author) {
					foreach ($author as $authorID=>$authorName) {
						if ($authorID && $authorID != 'external') {
							echo "<a href='" . $this->pathPersonenseite . "/" . substr($authorID, 0, -2) . "'>";
							//echo "<a href='/cris/person.shtml/" . substr($authorID, 0, -2) . "'>";
						}
						echo "<span class=\"author\">" . $authorName . "</span>";
						if ($authorID && $authorID != 'external') {
							echo "</a>";
						}
						echo ", ";
					}
				}
			echo ":";
			echo "<br /><span class=\"title\"><b>" . $pubDetails['title'] . "</b></span>";

			$this->echo_list($pubDetails);

			echo "</li>";

			} // end if
		}

		echo "</ul>";

	}

	/*
	 * Ausgabe Publikationen einzener Jahre
	 */
	public function publikationsjahre($jahr) {
		if(empty($this->publications)) {
			echo "<p>Es wurden leider keine Publikationen gefunden.</p>";
			//return;
		}
		echo "<ul>";

		foreach ($this->pubArray as $publication) {

			$authors = explode(", ", $publication['relAuthors']);
			$authorIDs = explode(",", $publication['relAuthorsId']);
			$authorsArray = array();
			foreach ($authorIDs as $i => $key) {
				$authorsArray[] = array($key => $authors[$i]);
			}

			$pubDetails = array(
				authorsArray => $authorsArray,
				title => strip_tags($publication['cfTitle']),
				city => strip_tags($publication['cfCityTown']),
				publisher => strip_tags($publication['publisher']),
				year => strip_tags($publication['publYear']),
				pubType => strip_tags($publication['Publication type']),
				pagesTotal => strip_tags($publication['cfTotalPages']),
				pagesRange => strip_tags($publication['pagesRange']),
				volume => strip_tags($publication['cfVol']),
				series => strip_tags($publication['cfSeries']),
				seriesNumber => strip_tags($publication['cfNum']),
				ISBN => strip_tags($publication['cfISBN']),
				ISSN => strip_tags($publication['cfISSN']),
				DOI => strip_tags($publication['DOI']),
				URI => strip_tags($publication['cfURI']),
				editiors => strip_tags($publication['Editor']),
				booktitle => strip_tags($publication['Edited Volumes']), // Titel des Sammelbands
				journaltitle => strip_tags($publication['journalName']),
				conference => strip_tags($publication['Conference']),
				origTitle => strip_tags($publication['Originaltitel']),
				origLanguage => strip_tags($publication['Language'])
			);

			if ($pubDetails['publYear'] == $jahr) {
				echo "<li style='margin-bottom: 15px; line-height: 150%;'>";
				foreach ($pubDetails['authorsArray'] as $author) {
					foreach ($author as $authorID=>$authorName) {
						if ($authorID && $authorID != 'external') {
							echo "<a href='" . $this->pathPersonenseite . "/" . substr($authorID, 0, -2) . "'>";
						}
						echo "<span class=\"author\">" . $authorName . "</span>";
						if ($authorID && $authorID != 'external') {
							echo "</a>";
						}
						echo ", ";
					}
				}
			echo ":";
			echo "<br /><span class=\"title\"><b>" . $pubDetails['title'] . "</b></span>";

			$this->echo_list($pubDetails);

			echo "</li>";

			} // end if
		}

		echo "</ul>";

	}

	/*
	 * Liste aller Publikationen in CRIS-Reihenfolge
	 */
	public function liste($titel) {

		if ($titel) {
			echo $this->titeltext;
		}

		if(empty($this->publications)) {
			echo "<p>Es wurden keine Publikationen gefunden.</p>";
		}

		echo "<ul>";
/*
			echo "<pre>";
			print_r($this->pubArray);
			echo "</pre>";
*/
		foreach ($this->pubArray as $publication) {
			$authors = explode(", ", $publication['relAuthors']);
			$authorIDs = explode(",", $publication['relAuthorsId']);
			$authorsArray = array();
			foreach ($authorIDs as $i => $key) {
				$authorsArray[] = array($key => $authors[$i]);
			}

			$pubDetails = array(
				authorsArray => $authorsArray,
				title => strip_tags($publication['cfTitle']),
				city => strip_tags($publication['cfCityTown']),
				publisher => strip_tags($publication['publisher']),
				year => strip_tags($publication['publYear']),
				pubType => strip_tags($publication['Publication type']),
				pagesTotal => strip_tags($publication['cfTotalPages']),
				pagesRange => strip_tags($publication['pagesRange']),
				volume => strip_tags($publication['cfVol']),
				series => strip_tags($publication['cfSeries']),
				seriesNumber => strip_tags($publication['cfNum']),
				ISBN => strip_tags($publication['cfISBN']),
				ISSN => strip_tags($publication['cfISSN']),
				DOI => strip_tags($publication['DOI']),
				URI => strip_tags($publication['cfURI']),
				editiors => strip_tags($publication['Editor']),
				booktitle => strip_tags($publication['Edited Volumes']), // Titel des Sammelbands
				journaltitle => strip_tags($publication['journalName']),
				conference => strip_tags($publication['Conference']),
				origTitle => strip_tags($publication['Originaltitel']),
				origLanguage => strip_tags($publication['Language'])
			);

			echo "<li style='margin-bottom: 15px; line-height: 150%;'>";

			// Autor: Link wenn in CRIS, Klartext wenn extern
			foreach ($pubDetails['authorsArray'] as $author) {
				foreach ($author as $authorID=>$authorName) {
					if ($authorID && $authorID != 'external') {
						echo "<a href='" . $this->pathPersonenseite . "/" . substr($authorID, 0, -2) . "'>";
						//echo "<a href='/cris/person.shtml/" . substr($authorID, 0, -2) . "'>";
					}
					echo "<span class=\"author\">" . $authorName . "</span>";
					if ($authorID && $authorID != 'external') {
						echo "</a>";
					}
					echo ", ";
				}
			}
			echo ":";
			// Titel
			echo "<br /><span class=\"title\"><b>" . $pubDetails['title'] . "</b></span>";

			// Bibliograhische Angaben, abhängig von Publikationstyp
			$this->echo_list($pubDetails);

			echo "</li>";
		}
		echo "</ul>";

	}

	/* =========================================================================
	 * Private Functions
	 ======================================================================== */

	/*
	 * Publikationsdaten nach Jahr sortieren
	 */
	private function record_sort($results) {

		// Define the custom sort function
		function custom_sort ($a, $b) { return $a['publYear']<$b['publYear'];}
		// Sort the multidimensional array
		uasort($results, "custom_sort");
		return $results;
	}


	/*
	 * Ausgabe der Publikationsdetails, unterschiedlich nach Publikationstyp
	 */
	private function echo_list($pubDetails) {

		switch ($pubDetails['pubType']) {

			case "Other": // Falling through
			case "Book":
				echo ((($pubDetails['city'] != '') || ($pubDetails['publisher'] != '') || ($pubDetails['year'] != '')) ? "<br />" : '');
				echo ($pubDetails['volume'] !='' ? $pubDetails['volume'] . ". "  : '');
				echo ($pubDetails['city'] !='' ? "<span class=\"city\">" . $pubDetails['city'] . "</span>: "  : '');
				echo ($pubDetails['publisher'] !='' ? $pubDetails['publisher'] . ", "  : '');
				echo ($pubDetails['year'] !='' ? $pubDetails['year'] : '');
				echo ($pubDetails['series'] !='' ? "<br />" . $pubDetails['series'] : '');
				echo ($pubDetails['seriesNumber'] !='' ? "Bd. " . $pubDetails['seriesNumber'] : '');
				echo ($pubDetails['pagesTotal'] !='' ? "<br />" . $pubDetails['pagesTotal'] . " Seiten" : '');
				echo ($pubDetails['ISBN'] !='' ? "<br />ISBN: " . $pubDetails['ISBN'] : '');
				echo ($pubDetails['ISSN'] !='' ? "<br />ISSN: " . $pubDetails['ISSN'] : '');
				echo ($pubDetails['DOI'] !='' ? "<br />DOI: <a href='http://dx.doi.org/" . $pubDetails['DOI'] . "' target='blank'>" . $pubDetails['DOI'] . "</a>"  : '');
				echo ($pubDetails['URI'] !='' ? "<br />URL: <a href='" . $pubDetails['URI'] . "' target='blank'>" . $pubDetails['URI'] . "</a>"  : '');
				break;

			case "Article in Edited Volumes":
				echo ((($pubDetails['editiors'] != '') || ($pubDetails['booktitle'] != '')) ? "<br />" : '');
				echo ($pubDetails['editiors'] !='' ?  "In: <strong>" . $pubDetails['editiors'] . '</strong> (Hrsg.):' : '');
				echo ($pubDetails['booktitle'] !='' ?  "In: <strong><em>" . $pubDetails['booktitle'] . '</em></strong>' : '');
				echo ((($pubDetails['city'] != '') || ($pubDetails['publisher'] != '') || ($pubDetails['year'] != '')) ? "<br />" : '');
				echo ($pubDetails['volume'] !='' ? $pubDetails['volume'] . ". "  : '');
				echo ($pubDetails['city'] !='' ? "<span class=\"city\">" . $pubDetails['city'] . "</span>: "  : '');
				echo ($pubDetails['publisher'] !='' ? $pubDetails['publisher'] . ", "  : '');
				echo ($pubDetails['year'] !='' ? $pubDetails['year'] : '');
				echo ($pubDetails['series'] !='' ? "<br />" . $pubDetails['series'] : '');
				echo ($pubDetails['seriesNumber'] !='' ? "Bd. " . $pubDetails['seriesNumber'] : '');
				echo ($pubDetails['pagesTotal'] !='' ? "<br />" . $pubDetails['pagesTotal'] . " Seiten" : '');
				echo ($pubDetails['ISBN'] !='' ? "<br />ISBN: " . $pubDetails['ISBN'] : '');
				echo ($pubDetails['ISSN'] !='' ? "<br />ISSN: " . $pubDetails['ISSN'] : '');
				echo ($pubDetails['DOI'] !='' ? "<br />DOI: <a href='http://dx.doi.org/" . $pubDetails['DOI'] . "' target='blank'>" . $pubDetails['DOI'] . "</a>"  : '');
				echo ($pubDetails['URI'] !='' ? "<br />URL: <a href='" . $pubDetails['URI'] . "' target='blank'>" . $pubDetails['URI'] . "</a>"  : '');
				break;

			case "Journal article":
				echo ((($pubDetails['journaltitle'] != '') || ($pubDetails['volume'] != '') || ($pubDetails['year'] != '') || ($pubDetails['pagesRange'] != '')) ? "<br />" : '');
				echo ($pubDetails['journaltitle'] !='' ?  "In: <strong>" . $pubDetails['journaltitle'] . '</strong> ' : '');
				echo ($pubDetails['volume'] !='' ? $pubDetails['volume'] . ". "  : '');
				echo ($pubDetails['year'] !='' ? " (" . $pubDetails['year'] . ")" : '');
				echo ($pubDetails['pagesRange'] !='' ? ", S. " . $pubDetails['pagesRange'] : '');
				echo ($pubDetails['DOI'] !='' ? "<br />DOI: <a href='http://dx.doi.org/" . $pubDetails['DOI'] . "' target='blank'>" . $pubDetails['DOI'] . "</a>"  : '');
				echo ($pubDetails['URI'] !='' ? "<br />URL: <a href='" . $pubDetails['URI'] . "' target='blank'>" . $pubDetails['URI'] . "</a>"  : '');
				break;

			case "Conference contribution":
				echo ((($pubDetails['conference'] != '') || ($pubDetails['publisher'] != '')) ? "<br />" : '');
				echo ($pubDetails['conference'] !='' ? $pubDetails['conference'] : '');
				echo ((($pubDetails['conference'] != '') && ($pubDetails['publisher'] != '')) ? ", " : '');
				echo ($pubDetails['publisher'] !='' ? $pubDetails['publisher'] : '');
				echo ((($pubDetails['city'] != '') || ($pubDetails['year'] != '')) ? "<br />" : '');
				echo ($pubDetails['city'] !='' ? "<span class=\"city\">" . $pubDetails['city'] . "</span>" : '');
				echo ($pubDetails['year'] !='' ? " (" . $pubDetails['year'] . ")" : '');
				echo ($pubDetails['DOI'] !='' ? "<br />DOI: <a href='http://dx.doi.org/" . $pubDetails['DOI'] . "' target='blank'>" . $pubDetails['DOI'] . "</a>"  : '');
				echo ($pubDetails['URI'] !='' ? "<br />URL: <a href='" . $pubDetails['URI'] . "' target='blank'>" . $pubDetails['URI'] . "</a>"  : '');
				break;
			case "Editorial":
				echo ($pubDetails['editors'] !='' ?  "<br />Hrsg: " . $pubDetails['editors'] : '');
				echo ((($pubDetails['city'] != '') || ($pubDetails['publisher'] != '') || ($pubDetails['year'] != '')) ? "<br />" : '');
				echo ($pubDetails['volume'] !='' ? $pubDetails['volume'] . ". "  : '');
				echo ($pubDetails['city'] !='' ? "<span class=\"city\">" . $pubDetails['city'] . "</span>: "  : '');
				echo ($pubDetails['publisher'] !='' ? $pubDetails['publisher'] . ", "  : '');
				echo ($pubDetails['year'] !='' ? $pubDetails['year'] : '');
				echo ($pubDetails['series'] !='' ? "<br />" . $pubDetails['series'] : '');
				echo ($pubDetails['seriesNumber'] !='' ? "Bd. " . $pubDetails['seriesNumber'] : '');
				echo ($pubDetails['pagesTotal'] !='' ? "<br />" . $pubDetails['pagesTotal'] . " Seiten" : '');
				echo ($pubDetails['ISBN'] !='' ? "<br />ISBN: " . $pubDetails['ISBN'] : '');
				echo ($pubDetails['ISSN'] !='' ? "<br />ISSN: " . $pubDetails['ISSN'] : '');
				echo ($pubDetails['DOI'] !='' ? "<br />DOI: <a href='http://dx.doi.org/" . $pubDetails['DOI'] . "' target='blank'>" . $pubDetails['DOI'] . "</a>"  : '');
				echo ($pubDetails['URI'] !='' ? "<br />URL: <a href='" . $pubDetails['URI'] . "' target='blank'>" . $pubDetails['URI'] . "</a>"  : '');
				break;
			case "Thesis":
				echo "<br />Abschlussarbeit";
				echo ($pubDetails['DOI'] !='' ? "<br />DOI: <a href='http://dx.doi.org/" . $pubDetails['DOI'] . "' target='blank'>" . $pubDetails['DOI'] . "</a>"  : '');
				echo ($pubDetails['URI'] !='' ? "<br />URL: <a href='" . $pubDetails['URI'] . "' target='blank'>" . $pubDetails['URI'] . "</a>"  : '');
				break;
			case "Translation":
				echo ((($pubDetails['city'] != '') || ($pubDetails['publisher'] != '') || ($pubDetails['year'] != '')) ? "<br />" : '');
				echo ($pubDetails['volume'] !='' ? $pubDetails['volume'] . ". "  : '');
				echo ($pubDetails['city'] !='' ? "<span class=\"city\">" . $pubDetails['city'] . "</span>: "  : '');
				echo ($pubDetails['publisher'] !='' ? $pubDetails['publisher'] . ", "  : '');
				echo ($pubDetails['series'] !='' ? "<br />" . $pubDetails['series'] : '');
				echo ($pubDetails['seriesNumber'] !='' ? "Bd. " . $pubDetails['seriesNumber'] : '');
				echo ($pubDetails['pagesTotal'] !='' ? "<br />" . $pubDetails['pagesTotal'] . " Seiten" : '');
				echo ($pubDetails['ISBN'] !='' ? "<br />ISBN: " . $pubDetails['ISBN'] : '');
				echo ($pubDetails['ISSN'] !='' ? "<br />ISSN: " . $pubDetails['ISSN'] : '');
				echo ($pubDetails['DOI'] !='' ? "<br />DOI: <a href='http://dx.doi.org/" . $pubDetails['DOI'] . "' target='blank'>" . $pubDetails['DOI'] . "</a>"  : '');
				echo ($pubDetails['URI'] !='' ? "<br />URL: <a href='" . $pubDetails['URI'] . "' target='blank'>" . $pubDetails['URI'] . "</a>"  : '');
				echo ($pubDetails['origTitle'] !='' ? "<br />Originaltitel: " . $pubDetails['origTitle'] : '');
				echo ($pubDetails['origLanguage'] !='' ? "<br />Originalsprache: " . $pubDetails['origLanguage'] : '');
				break;
		}
	}
}