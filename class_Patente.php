<?php

require_once("class_Tools.php");
require_once("class_Webservice.php");
require_once("class_Filter.php");
require_once("class_Formatter.php");

class Patente {

    private $options;
    public $output;

    public function __construct($einheit = '', $id = '') {

        if (strpos($_SERVER['PHP_SELF'], "vkdaten/tools/")) {
            $this->cms = 'wbk';
            $this->options = CRIS::ladeConf();
            $this->pathPersonenseiteUnivis = $this->options['Pfad_Personenseite_Univis'] . '/';
        } else {
            $this->cms = 'wp';
            $this->options = (array) get_option('_fau_cris');
            $this->pathPersonenseiteUnivis = '/person/';
        }
        $this->orgNr = $this->options['cris_org_nr'];
        $this->suchstring = '';
        $this->univis = NULL;

        $this->order = $this->options['cris_patent_order'];
        $this->cris_patent_link = isset($this->options['cris_patent_link']) ? $this->options['cris_patent_link'] : 'none';
        if ($this->cms == 'wbk' && $this->cris_patent_link == 'person') {
            $this->univis = Tools::get_univis();
        }

        if ((!$this->orgNr || $this->orgNr == 0) && $id == '') {
            print '<p><strong>' . __('Bitte geben Sie die CRIS-ID der Organisation, Person oder des Patents an.', 'fau-cris') . '</strong></p>';
            return;
        }
        if (in_array($einheit, array("person", "orga", "patent"))) {
            $this->id = $id;
            $this->einheit = $einheit;
        } else {
            // keine Einheit angegeben -> OrgNr aus Einstellungen verwenden
            $this->id = $this->orgNr;
            $this->einheit = "orga";
        }

    }

    /*
     * Ausgabe aller Patente ohne Gliederung
     */

    public function patListe($year = '', $start = '', $type = '', $showname = 1, $showyear = 1, $showpatentname = 1) {
        $patentArray = $this->fetch_patents($year, $start, $type);

        if (!count($patentArray)) {
            $output = '<p>' . __('Es wurden leider keine Patente gefunden.', 'fau-cris') . '</p>';
            return $output;
        }

        $order = "year patent";
        $formatter = new CRIS_formatter(NULL, NULL, $order, SORT_DESC);
        $res = $formatter->execute($patentArray);
        $patentList = $res[$order];

        $output = $this->make_list($patentList, $showname, $showyear, $showpatentname);

        return $output;
    }

    /*
     * Ausgabe aller Patente nach Jahren gegliedert
     */

    public function patNachJahr($year = '', $start = '', $type = '', $showname = 1, $showyear = 0, $showpatentname = 1, $order2 = 'year') {
        $patentArray = $this->fetch_patents($year, $start, $type);

        if (!count($patentArray)) {
            $output = '<p>' . __('Es wurden leider keine Patente gefunden.', 'fau-cris') . '</p>';
            return $output;
        }

        if ($order2 == 'author') {
            $formatter = new CRIS_formatter("registryear", SORT_DESC, "exportinventors", SORT_ASC);
        } else {
            $formatter = new CRIS_formatter("registryear", SORT_DESC, "cfregistrdate", SORT_ASC);
        }
        $patentList = $formatter->execute($patentArray);

        $output = '';

        foreach ($patentList as $array_year => $patents) {
            if (empty($year)) {
                $output .= '<h3 class="clearfix clear">';
                $output .=!empty($array_year) ? $array_year : __('Ohne Jahr', 'fau-cris');
                $output .= '</h3>';
            }
            $output .= $this->make_list($patents, $showname, $showyear, $showpatentname);
        }

        return $output;
    }

    /*
     * Ausgabe aller Patente nach Patenttypen gegliedert
     */

    public function patNachTyp($year = '', $start = '', $type = '', $showname = 1, $showyear = 0, $showpatentname = 1, $order2 = 'year') {
        $patentArray = $this->fetch_patents($year, $start, $type);

        if (!count($patentArray)) {
            $output = '<p>' . __('Es wurden leider keine Patente gefunden.', 'fau-cris') . '</p>';
            return $output;
        }

        // Patenttypen sortieren
        $order = $this->order;
        if ($order[0] != '' && array_search($order[0], array_column(CRIS_Dicts::$publications, 'short'))) {
            foreach ($order as $key => $value) {
                $order[$key] = Tools::getType('patents', $value);
            }
        } else {
            $order = Tools::getOrder('patents');
        }

        // sortiere nach Typenliste, innerhalb des Typs nach Name aufwärts sortieren
        if ($order2 == 'name') {
            $formatter = new CRIS_formatter("patenttype", SORT_DESC, "exportinventors", SORT_ASC);
        } else {
            $formatter = new CRIS_formatter("patenttype", SORT_DESC, "cfregistrdate", SORT_DESC);
        }
        $patentList = $formatter->execute($patentArray);
        $output = '';

        foreach ($patentList as $array_type => $patents) {
            if (empty($type)) {
                $title = Tools::getTitle('patents', $array_type, get_locale());
                $output .= '<h3 class="clearfix clear">';
                $output .= $title;
                $output .= "</h3>";
            }
            $output .= $this->make_list($patents, $showname, $showyear, $showpatentname, 0);
        }

        return $output;
    }

    /*
     * Ausgabe eines einzelnen Patents
     */

    public function singlePatent($showname = 1, $showyear = 0, $showpatentname = 1) {
        $ws = new CRIS_patents();

        try {
            $patentArray = $ws->by_id($this->id);
        } catch (Exception $ex) {
            return;
        }

        if (!count($patentArray)) {
            $output = '<p>' . __('Es wurden leider keine Patente gefunden.', 'fau-cris') . '</p>';
            return $output;
        }

        $output = $this->make_list($patentArray, $showname, $showyear, $showpatentname);

        return $output;
    }

    /* =========================================================================
     * Private Functions
      ======================================================================== */

    /*
     * Holt Daten vom Webservice je nach definierter Einheit.
     */

    private function fetch_patents($year = '', $start = '', $type = '') {
        $filter = Tools::patent_filter($year, $start, $type);

        $ws = new CRIS_patents();
        $patentArray = array();

        try {
            if ($this->einheit === "orga") {
                $patentArray = $ws->by_orga_id($this->id, $filter);
            }
            if ($this->einheit === "person") {
                $patentArray = $ws->by_pers_id($this->id, $filter);
            }
        } catch (Exception $ex) {
            $patentArray = array();
        }
        return $patentArray;
    }

    /*
     * Ausgabe der Patents
     */

    private function make_list($patents, $name = 1, $year = 1, $patentname = 1, $showtype = 1) {
        $patentlist = "<ul class=\"cris-patents\">";

        foreach ($patents as $patent) {
            $patent = (array) $patent;
            foreach ($patent['attributes'] as $attribut => $v) {
                $patent[$attribut] = $v;
            }
            unset($patent['attributes']);

            $inventors = explode("|", $patent['exportinventors']);
            $inventorIDs = explode(",", $patent['relinventorsid']);
            $inventorsArray = array();
            foreach ($inventorIDs as $i => $key) {
                $inventorsArray[] = array('id' => $key, 'name' => $inventors[$i]);
            }
            $inventorsList = array();
            foreach ($inventorsArray as $inventor) {
                $inventor_elements = explode(":", $inventor['name']);
                $inventor_firstname = $inventor_elements[1];
                $inventor_lastname = $inventor_elements[0];
                $inventorsList[] = Tools::get_person_link($inventor['id'], $inventor_firstname, $inventor_lastname, $this->cris_patent_link, $this->cms, $this->pathPersonenseiteUnivis, $this->univis, 0);
            }
            $inventors_html = implode(", ", $inventorsList);

            $patent_id = $patent['ID'];
            $lang = strpos(get_locale(), 'de') === 0 ? 'de' : 'en';
            $patent_name = ($lang == 'de') ? $patent['cftitle'] : $patent['cftitle_en'];
            $patent_type = Tools::getName('patents', $patent['patenttype'], get_locale());
            $patent_abstract = $patent['cfabstr'];
            $patent_number = $patent['cfpatentnum'];
            $patent_link = $patent['patnrlink'];
            setlocale(LC_TIME, get_locale());
            $patent_registered = $patent['cfregistrdate'];
            $patent_registered = strftime('%x', strtotime($patent_registered));
            $patent_appproved = $patent['cfapprovdate'];
            $patent_appproved = strftime('%x', strtotime($patent_appproved));
            $patent_expiry = $patent['patexpirydate'];
            $patent_expiry = strftime('%x', strtotime($patent_expiry));

            $patentlist .= "<li>";

            if (!empty($patent_name))
                $patentlist .= "<strong><a href=\"https://cris.fau.de/converis/publicweb/cfrespat/" . $patent_id . "\" target=\"blank\" title=\"" . __('Detailansicht auf cris.fau.de in neuem Fenster &ouml;ffnen', 'fau-cris') . "\">" . $patent_name . "</a></strong>";
            if (!empty($patent_type) || !empty($patent_number))
                $patentlist .= " (";
            if (!empty($patent_type) & $showtype != 0)
                $patentlist .= $patent_type . ": ";
            if (!empty($patent_number)) {
                if (!empty($patent_link))
                    $patentlist .= "<a href=\"" . $patent_link . "\ target=\"blank\" title=\"" . __('Eintrag auf DEPATISnet in neuem Fenster &ouml;ffnen', 'fau-cris') . "\">";
                $patentlist .= $patent_number;
                if (!empty($patent_link))
                    $patentlist .= "</a>";
            }
            if (!empty($patent_type) && !empty($patent_number))
                $patentlist .= ")";
            if (!empty($inventors))
                $patentlist .= "<br />" . __('Erfinder', 'fau-cris') . ": " . $inventors_html;
           $patentlist .= "</li>";
        }

        $patentlist .= "</ul>";
        return $patentlist;
    }

}

class CRIS_patents extends CRIS_webservice {
    /*
     * patents/grants requests
     */

    public function by_orga_id($orgaID = null, &$filter = null) {
        if ($orgaID === null || $orgaID === "0")
            throw new Exception('Please supply valid organisation ID');

        if (!is_array($orgaID))
            $orgaID = array($orgaID);

        $requests = array();
        foreach ($orgaID as $_o) {
            $requests[] = sprintf("getautorelated/Organisation/%d/ORGA_2_PATE_1", $_o);
        }
        return $this->retrieve($requests, $filter);
    }

    public function by_pers_id($persID = null, &$filter = null) {
        if ($persID === null || $persID === "0")
            throw new Exception('Please supply valid person ID');

        if (!is_array($persID))
            $persID = array($persID);

        $requests = array();
        foreach ($persID as $_p) {
            $requests[] = sprintf('getautorelated/Person/%d/PERS_2_PATE_1', $_p);
        }
        return $this->retrieve($requests, $filter);
    }

    public function by_id($awarID = null) {
        if ($awarID === null || $awarID === "0")
            throw new Exception('Please supply valid patent ID');

        if (!is_array($awarID))
            $awarID = array($awarID);

        $requests = array();
        foreach ($awarID as $_p) {
            $requests[] = sprintf('get/cfrespat/%d', $_p);
        }
        return $this->retrieve($requests);
    }

    private function retrieve($reqs, &$filter = null) {
        if ($filter !== null && !$filter instanceof CRIS_filter)
            $filter = new CRIS_filter($filter);

        $data = array();
        foreach ($reqs as $_i) {
            try {
                $data[] = $this->get($_i, $filter);
            } catch (Exception $e) {
                // TODO: logging?
//                $e->getMessage();
                continue;
            }
        }

        $patents = array();

        foreach ($data as $_d) {
            foreach ($_d as $patent) {
                $a = new CRIS_patent($patent);
                if ($a->ID) {
                    $a->attributes['registryear'] = substr($a->attributes['cfregistrdate'], 0, 4);
                    $a->attributes['approvyear'] = $a->attributes['cfapprovdate'] != '' ? substr($a->attributes['cfapprovdate'], 0, 4) : '';
                    $a->attributes['expiryyear'] = $a->attributes['patexpirydate'] != '' ? substr($a->attributes['patexpirydate'], 0, 4) : '';
                }

                if ($a->ID && ($filter === null || $filter->evaluate($a)))
                    $patents[$a->ID] = $a;
            }
        }

        return $patents;
    }

}

class CRIS_patent extends CRIS_Entity {
    /*
     * object for single patent
     */

    function __construct($data) {
        parent::__construct($data);
    }

}
