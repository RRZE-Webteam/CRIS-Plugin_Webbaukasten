<?php

require_once('class_CRIS.php');
require_once("class_Tools.php");
require_once("class_Publications.php");

class Publikationen_neu {

    private $options;
    public $output;


    public function __construct($einheit = '', $id = '') {
        $getoptions = new CRIS();
        $this->options = $getoptions->options;
        $orgNr = $this->options['CRISOrgNr'];
        $this->pathPersonenseite = $this->options['Pfad_Personenseite'];
        $this->pathPersonenseiteUnivis = $this->options['Pfad_Personenseite_Univis'];
        $this->pubOrder = $this->options['Reihenfolge_Publikationen'];
        $this->suchstring = '';
        switch (substr($this->options['Sprache'], 0, 2)) {
            case 'de':
                $this->locale = 'de_DE';
                break;
            case 'en':
                $this->locale = 'en_US';
                break;
            default:
                $this->locale = $this->options['Sprache'];
        }
        $locale = $this->locale;

        // Sprache einstellen
        putenv('LC_ALL=' . $this->locale);
        setlocale(LC_ALL, $this->locale);
        // Pfads der Übersetzungstabellen
        bindtextdomain("fau-cris", "./languages");
        // Domain auswählen
        textdomain("fau-cris");

        // WP-Funktionen ersetzen
        if (!function_exists("__")) {
            function __($text, $x=null) {
                return _($text);
            }
        }
        if (!function_exists("get_locale()")) {
            function get_locale() {
                global $locale;
                return $locale;
            }
        }

        if ((!$orgNr || $orgNr == 0) && $id == '') {
            print '<p><strong>' . __('Bitte geben Sie die CRIS-ID der Organisation, Person oder Publikation an.', 'fau-cris') . '</strong></p>';
            return;
        }
        if (in_array($einheit, array("person", "orga", "publication"))) {
            $this->id = $id;
            $this->einheit = $einheit;
        } else {
            // keine Einheit angegeben -> OrgNr aus Einstellungen verwenden
            $this->id = $orgNr;
            $this->einheit = "orga";
        }
        // Mitarbeiter dieser Organisationseinheit (damit nur diese später verlinkt werden)
        $suchstringOrga = 'https://cris.fau.de/ws-cached/1.0/public/infoobject/getrelated/Organisation/' . $orgNr . '/CARD_has_ORGA';
        $xmlOrga = Tools::XML2obj($suchstringOrga);
        foreach ($xmlOrga as $card) {
            $this->inOrga[] = (string) $card['id'];
        }
    }

    /*
     * Ausgabe aller Publikationen ohne Gliederung
     */

    public function pubListe($year = '', $start = '', $type = '', $quotation = '', $items = '') {
        $pubArray = $this->fetch_publications($year, $start, $type);

        if (!count($pubArray)) {
            $output = '<p>' . __('Es wurden leider keine Publikationen gefunden.', 'fau-cris') . '</p>';
            return $output;
        }

        // sortiere nach Erscheinungsdatum
        $order = "virtualdate";
        $formatter = new CRIS_formatter(NULL, NULL, $order, SORT_DESC);
        $res = $formatter->execute($pubArray);
        if ($items != '')
            $pubList = array_slice($res[$order], 0, $items);
        else
            $pubList = $res[$order];

        $output = '';

        if ($quotation == 'apa' || $quotation == 'mla') {
            $output .= $this->make_quotation_list($pubList, $quotation);
        } else {
            $output .= $this->make_list($pubList);
        }

        return $output;
    }

    /*
     * Ausgabe aller Publikationen nach Jahren gegliedert
     */

    public function pubNachJahr($year = '', $start = '', $type = '', $quotation = '') {
        $pubArray = $this->fetch_publications($year, $start, $type);
        if (!count($pubArray)) {
            $output = '<p>' . __('Es wurden leider keine Publikationen gefunden.', 'fau-cris') . '</p>';
            return $output;
        }
        // sortiere nach Erscheinungsjahr, innerhalb des Jahres nach Erstautor
        $formatter = new CRIS_formatter("publyear", SORT_DESC, "relauthors", SORT_ASC);
        $pubList = $formatter->execute($pubArray);
        $output = '';
        foreach ($pubList as $array_year => $publications) {
            if (empty($year)) {
                $output .= '<h3>' . $array_year . '</h3>';
            }
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
        $pubArray = $this->fetch_publications($year, $start, $type);
        if (!count($pubArray)) {
            $output = '<p>' . __('Es wurden leider keine Publikationen gefunden.', 'fau-cris') . '</p>';
            return $output;
        }
        // Publikationstypen sortieren
        $order = $this->options['Reihenfolge_Publikationen'];
        if ($order[0] != '' && array_key_exists($order[0], CRIS_Dicts::$pubNames)) {
            foreach ($order as $key => $value) {
                $order[$key] = Tools::getPubName($value, "en");
            }
        } else {
            $order = array();
            foreach (CRIS_Dicts::$pubOrder as $value) {
                $order[] = Tools::getPubName($value, "en");
            }
        }
//            print_r($order);
        // sortiere nach Typenliste, innerhalb des Jahres nach Jahr abwärts sortieren
        $formatter = new CRIS_formatter("publication type", array_values($order), "publyear", SORT_DESC);
        $pubList = $formatter->execute($pubArray);
        $output = '';
        foreach ($pubList as $array_type => $publications) {
            // Zwischenüberschrift (= Publikationstyp), außer wenn nur ein Typ gefiltert wurde
            if (empty($type)) {
                $title = Tools::getpubTitle($array_type, get_locale());
                $output .= "<h3>";
                $output .= $title;
                $output .= "</h3>";
            }

            if ($quotation == 'apa' || $quotation == 'mla') {
                $output .= $this->make_quotation_list($publications, $quotation);
            } else {
                $output .= $this->make_list($publications);
            }
        }
        return $output;
    }

// Ende pubNachTyp()

    public function singlePub($quotation = '') {
        $ws = new CRIS_publications();

        try {
            $pubArray = $ws->by_id($this->id);
        } catch (Exception $ex) {
            return;
        }

        if (!count($pubArray))
            return;

        if ($quotation == 'apa' || $quotation == 'mla') {
            $output = $this->make_quotation_list($pubArray, $quotation);
        } else {
            $output = $this->make_list($pubArray);
        }

        return $output;
    }

    /* =========================================================================
     * Private Functions
      ======================================================================== */

    /*
     * Holt Daten vom Webservice je nach definierter Einheit.
     */

    private function fetch_publications($year = '', $start = '', $type = '') {
        $filter = Tools::publication_filter($year, $start, $type);

        $ws = new CRIS_publications();

        try {
            if ($this->einheit === "orga") {
                $pubArray = $ws->by_orga_id($this->id, $filter);
            }
            if ($this->einheit === "person") {
                $pubArray = $ws->by_pers_id($this->id, $filter);
            }
        } catch (Exception $ex) {
            $pubArray = array();
        }
        return $pubArray;
    }

    /*
     * Ausgabe der Publikationsdetails in Zitierweise (MLA/APA)
     */

    private function make_quotation_list($publications, $quotation) {

        $quotation = strtolower($quotation);
        $publist = "<ul class=\"cris-publications\">";

        foreach ($publications as $publication) {
            $publist .= "<li>";
            $publist .= $publication->attributes['quotation' . $quotation];
            if (isset($this->options['cris_bibtex']) && $this->options['cris_bibtex'] == 1) {
                $publist .= "<br />BibTeX: " . $publication->attributes['bibtex_link'];
                //$publist .= "<br />BibTeX: <a href=\"http:/ /cris.fau.de/bibtex/publication/ID.bib\">http:/ /cris.fau.de/bibtex/publication/" . $pubDetails['ID'] . ".bib</a>";
            }
            $publist .= "</li>";
        }

        $publist .= "</ul>";

        return $publist;
    }

    /*
     * Ausgabe der Publikationsdetails, unterschiedlich nach Publikationstyp
     */

    private function make_list($publications) {

        $publist = "<ul>";

        foreach ($publications as $publicationObject) {

            $publication = $publicationObject->attributes;
            $id = $publicationObject->ID;

            $authors = explode(", ", $publication['relauthors']);
            $authorIDs = explode(",", $publication['relauthorsid']);
            $authorsArray = array();
            foreach ($authorIDs as $i => $key) {
                $authorsArray[] = array('id' => $key, 'name' => $authors[$i]);
            }

            $pubDetails = array(
                'id' => $id,
                'authorsArray' => $authorsArray,
                'title' => (array_key_exists('cftitle', $publication) ? strip_tags($publication['cftitle']) : __('O.T.', 'fau-cris')),
                'city' => (array_key_exists('cfcitytown', $publication) ? strip_tags($publication['cfcitytown']) : __('O.O.', 'fau-cris')),
                'publisher' => (array_key_exists('publisher', $publication) ? strip_tags($publication['publisher']) : __('O.A.', 'fau-cris')),
                'year' => (array_key_exists('publyear', $publication) ? strip_tags($publication['publyear']) : __('O.J.', 'fau-cris')),
                'pubType' => (array_key_exists('publication type', $publication) ? strip_tags($publication['publication type']) : __('O.A.', 'fau-cris')),
                'pagesTotal' => (array_key_exists('cftotalpages', $publication) ? strip_tags($publication['cftotalpages']) : ''),
                'pagesRange' => (array_key_exists('pagesrange', $publication) ? strip_tags($publication['pagesrange']) : ''),
                'volume' => (array_key_exists('cfvol', $publication) ? strip_tags($publication['cfvol']) : __('O.A.', 'fau-cris')),
                'series' => (array_key_exists('cfseries', $publication) ? strip_tags($publication['cfseries']) : __('O.A.', 'fau-cris')),
                'seriesNumber' => (array_key_exists('cfnum', $publication) ? strip_tags($publication['cfnum']) : __('O.A.', 'fau-cris')),
                'ISBN' => (array_key_exists('cfisbn', $publication) ? strip_tags($publication['cfisbn']) : __('O.A.', 'fau-cris')),
                'ISSN' => (array_key_exists('cfissn', $publication) ? strip_tags($publication['cfissn']) : __('O.A.', 'fau-cris')),
                'DOI' => (array_key_exists('doi', $publication) ? strip_tags($publication['doi']) : __('O.A.', 'fau-cris')),
                'URI' => (array_key_exists('cfuri', $publication) ? strip_tags($publication['cfuri']) : __('O.A.', 'fau-cris')),
                'editiors' => (array_key_exists('editor', $publication) ? strip_tags($publication['editor']) : __('O.A.', 'fau-cris')),
                'booktitle' => (array_key_exists('edited volumes', $publication) ? strip_tags($publication['edited volumes']) : __('O.A.', 'fau-cris')), // Titel des Sammelbands
                'journaltitle' => (array_key_exists('journalname', $publication) ? strip_tags($publication['journalname']) : __('O.A.', 'fau-cris')),
                'conference' => (array_key_exists('conference', $publication) ? strip_tags($publication['conference']) : 'O.A.'),
                'origTitle' => (array_key_exists('originaltitel', $publication) ? strip_tags($publication['originaltitel']) : __('O.A.', 'fau-cris')),
                'origLanguage' => (array_key_exists('language', $publication) ? strip_tags($publication['language']) : __('O.A.', 'fau-cris')),
                'bibtex_link' => (array_key_exists('bibtex_link', $publication) ? $publication['bibtex_link'] : __('Nicht verfügbar', 'fau-cris'))
            );

            $publist .= "<li>";

            $authorList = array();
            foreach ($pubDetails['authorsArray'] as $author) {
                $span_pre = "<span class=\"author\">";
                $span_post = "</span>";
                $authordata = $span_pre . $author['name'] . $span_post;
                $author_firstname = explode(" ", $author['name'])[1];
                $author_lastname = explode(" ", $author['name'])[0];
                if ($author['id'] && !in_array($author['id'], array('invisible', 'external')) && isset($this->options['cris_univis']) && $this->options['cris_univis'] == 1 && Tools::person_slug($author_firstname, $author_lastname) != "") {
                    $link_pre = "<a href=\"/person/" . Tools::person_slug($author_firstname, $author_lastname) . "\">";
                    $link_post = "</a>";
                    $authordata = $link_pre . $authordata . $link_post;
                }
                $authorList[] = $authordata;
            }
            $publist .= implode(", ", $authorList);
            $publist .= ($pubDetails['pubType'] == 'Editorial' ? '(' . __('Hrsg.', 'fau-cris') . '):' : ':');

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
                    $publist .= ($pubDetails['pagesTotal'] != '' ? "<br />" . $pubDetails['pagesTotal'] . " " . __('Seiten', 'fau-cris') : '');
                    $publist .= ($pubDetails['ISBN'] != '' ? "<br />ISBN: " . $pubDetails['ISBN'] : '');
                    $publist .= ($pubDetails['ISSN'] != '' ? "<br />ISSN: " . $pubDetails['ISSN'] : '');
                    $publist .= ($pubDetails['DOI'] != '' ? "<br />DOI: <a href='http://dx.doi.org/" . $pubDetails['DOI'] . "' target='blank'>" . $pubDetails['DOI'] . "</a>" : '');
                    $publist .= ($pubDetails['URI'] != '' ? "<br />URL: <a href='" . $pubDetails['URI'] . "' target='blank'>" . $pubDetails['URI'] . "</a>" : '');
                    break;

                case "Article in Edited Volumes":
                    $publist .= ((($pubDetails['editiors'] != '') || ($pubDetails['booktitle'] != '')) ? "<br />" : '');
                    $publist .= ($pubDetails['editiors'] != '' ? "In: <strong>" . $pubDetails['editiors'] . ' (' . __('Hrsg.', 'fau-cris') . '): </strong>' : '');
                    $publist .= ($pubDetails['booktitle'] != '' ? " <strong><em>" . $pubDetails['booktitle'] . '</em></strong>' : '');
                    $publist .= ((($pubDetails['city'] != '') || ($pubDetails['publisher'] != '') || ($pubDetails['year'] != '')) ? "<br />" : '');
                    $publist .= ($pubDetails['volume'] != '' ? $pubDetails['volume'] . ". " : '');
                    $publist .= ($pubDetails['city'] != '' ? "<span class=\"city\">" . $pubDetails['city'] . "</span>: " : '');
                    $publist .= ($pubDetails['publisher'] != '' ? $pubDetails['publisher'] . ", " : '');
                    $publist .= ($pubDetails['year'] != '' ? $pubDetails['year'] : '');
                    $publist .= ($pubDetails['series'] != '' ? "<br />" . $pubDetails['series'] : '');
                    $publist .= ($pubDetails['seriesNumber'] != '' ? "Bd. " . $pubDetails['seriesNumber'] : '');
                    $publist .= ($pubDetails['pagesTotal'] != '' ? "<br />" . $pubDetails['pagesTotal'] . " " . __('Seiten', 'fau-cris') : '');
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
                    $publist .= ($pubDetails['pagesRange'] != '' ? ", " . __('S.', 'Abkürzung für "Seite" bei Publikationen', 'fau-cris') . " " . $pubDetails['pagesRange'] : '');
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
                    $publist .= ($pubDetails['pagesTotal'] != '' ? "<br />" . $pubDetails['pagesTotal'] . " " . __('Seiten', 'fau-cris') : '');
                    $publist .= ($pubDetails['ISBN'] != '' ? "<br />ISBN: " . $pubDetails['ISBN'] : '');
                    $publist .= ($pubDetails['ISSN'] != '' ? "<br />ISSN: " . $pubDetails['ISSN'] : '');
                    $publist .= ($pubDetails['DOI'] != '' ? "<br />DOI: <a href='http://dx.doi.org/" . $pubDetails['DOI'] . "' target='blank'>" . $pubDetails['DOI'] . "</a>" : '');
                    $publist .= ($pubDetails['URI'] != '' ? "<br />URL: <a href='" . $pubDetails['URI'] . "' target='blank'>" . $pubDetails['URI'] . "</a>" : '');
                    break;
                case "Thesis":
                    $publist .= "<br />Abschlussarbeit " . $pubDetails['year'];
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
                    $publist .= ($pubDetails['pagesTotal'] != '' ? "<br />" . $pubDetails['pagesTotal'] . " " . __('Seiten', 'fau-cris') : '');
                    $publist .= ($pubDetails['ISBN'] != '' ? "<br />ISBN: " . $pubDetails['ISBN'] : '');
                    $publist .= ($pubDetails['ISSN'] != '' ? "<br />ISSN: " . $pubDetails['ISSN'] : '');
                    $publist .= ($pubDetails['DOI'] != '' ? "<br />DOI: <a href='http://dx.doi.org/" . $pubDetails['DOI'] . "' target='blank'>" . $pubDetails['DOI'] . "</a>" : '');
                    $publist .= ($pubDetails['URI'] != '' ? "<br />URL: <a href='" . $pubDetails['URI'] . "' target='blank'>" . $pubDetails['URI'] . "</a>" : '');
                    $publist .= ($pubDetails['origTitle'] != '' ? "<br />Originaltitel: " . $pubDetails['origTitle'] : '');
                    $publist .= ($pubDetails['origLanguage'] != '' ? "<br />Originalsprache: " . $pubDetails['origLanguage'] : '');
                    break;
            }
            if (isset($this->options['cris_bibtex']) && $this->options['cris_bibtex'] == 1) {
                $publist .= "<br />BibTeX: " . $pubDetails['bibtex_link'];
                //$publist .= "<br />BibTeX: <a href=\"http:/ /cris.fau.de/bibtex/publication/ID.bib\">http:/ /cris.fau.de/bibtex/publication/" . $pubDetails['ID'] . ".bib</a>";
            }
            $publist .= "</li>";
        }
        $publist .= "</ul>";

        return $publist;
    }

}
