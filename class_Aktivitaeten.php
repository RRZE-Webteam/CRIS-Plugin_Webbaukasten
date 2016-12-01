<?php

require_once("class_Tools.php");
require_once("class_Webservice.php");
require_once("class_Filter.php");
require_once("class_Formatter.php");

class Aktivitaeten {

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

        $this->order = $this->options['cris_activities_order'];
        $this->cris_activities_link = isset($this->options['cris_activities_link']) ? $this->options['cris_activities_link'] : 'none';
        if ($this->cms == 'wbk' && $this->univisLink == 'person') {
            $this->univis = Tools::get_univis();
        }

        if ((!$this->orgNr || $this->orgNr == 0) && $id == '') {
            print '<p><strong>' . __('Bitte geben Sie die CRIS-ID der Organisation, Person oder Forschungsaktivität an.', 'fau-cris') . '</strong></p>';
            return;
        }
        if (in_array($einheit, array("person", "orga", "activity"))) {
            $this->id = $id;
            $this->einheit = $einheit;
        } else {
            // keine Einheit angegeben -> OrgNr aus Einstellungen verwenden
            $this->id = $this->orgNr;
            $this->einheit = "orga";
        }

    }

    /*
     * Ausgabe aller Aktivitäten ohne Gliederung
     */

    public function actiListe($year = '', $start = '', $type = '', $items='', $hide='') {
        $showname = $this->einheit == 'person' ? 0 : 1;
        $showyear = 1;
        $showactivityname = 1;

        $activityArray = $this->fetch_activities($year, $start, $type);

        if (!count($activityArray)) {
            $output = '<p>' . __('Es wurden leider keine Aktivitäten gefunden.', 'fau-cris') . '</p>';
            return $output;
        }
        $order = "sortdate";
        $formatter = new CRIS_formatter(NULL, NULL, $order, SORT_DESC);
        $res = $formatter->execute($activityArray);
        $activityList = $res[$order];

        $output = $this->make_list($activityList, $showname, $showyear, $showactivityname);

        return $output;
    }

    /*
     * Ausgabe aller Aktivitäten nach Jahren gegliedert
     */

    public function actiNachJahr($year = '', $start = '', $type = '', $hide= '') {
        $showname = $this->einheit == 'person' ? 0 : 1;
        $showyear = 0;
        $showactivityname = 1;
        $order2 = 'year';
        $activityArray = $this->fetch_activities($year, $start, $type);

        if (!count($activityArray)) {
            $output = '<p>' . __('Es wurden leider keine Aktivitäten gefunden.', 'fau-cris') . '</p>';
            return $output;
        }

        if ($order2 == 'author') {
            $formatter = new CRIS_formatter("year", SORT_DESC, "exportnames", SORT_ASC);
        } else {
            $formatter = new CRIS_formatter("year", SORT_DESC, "sortdate", SORT_ASC);
        }
        $activityList = $formatter->execute($activityArray);

        $output = '';

        foreach ($activityList as $array_year => $activities) {
            if (empty($year)) {
                $output .= '<h3 class="clearfix clear">';
                $output .=!empty($array_year) ? $array_year : __('Ohne Jahr', 'fau-cris');
                $output .= '</h3>';
            }
            $output .= $this->make_list($activities, $showname, $showyear, $showactivityname);
        }

        return $output;
    }

    /*
     * Ausgabe aller Aktivitäten nach Patenttypen gegliedert
     */

    public function actiNachTyp($year = '', $start = '', $type = '', $hide ='') {
        $showname = $this->einheit == 'person' ? 0 : 1;
        $showyear = 0;
        $showactivityname = 1;
        $order2 = 'year';

        $activityArray = $this->fetch_activities($year, $start, $type);

        if (!count($activityArray)) {
            $output = '<p>' . __('Es wurden leider keine Aktivitäten gefunden.', 'fau-cris') . '</p>';
            return $output;
        }

        // Patenttypen sortieren
        $order = $this->order;
        if ($order[0] != '' && array_search($order[0], array_column(CRIS_Dicts::$activities, 'short'))) {
            foreach ($order as $key => $value) {
                $order[$key] = Tools::getType('activities', $value);
            }
        } else {
            $order = Tools::getOrder('activities');
        }

        // sortiere nach Typenliste, innerhalb des Typs nach Name aufwärts sortieren
        if ($order2 == 'name') {
            $formatter = new CRIS_formatter("type of activity", $order, "exportnames", SORT_ASC);
        } else {
            $formatter = new CRIS_formatter("type of activity", $order, "sortdate", SORT_DESC);
        }
        $activityList = $formatter->execute($activityArray);
        $output = '';

        foreach ($activityList as $array_type => $activities) {
            if (empty($type)) {
                $title = Tools::getTitle('activities', $array_type, get_locale());
                $output .= '<h3 class="clearfix clear">';
                $output .= $title;
                $output .= "</h3>";
            }
            $output .= $this->make_list($activities, $showname, $showyear, $showactivityname, 0);
        }

        return $output;
    }

    /*
     * Ausgabe eines einzelnen Patents
     */

    public function singleActivity($hide) {
        $showname = 1;
        $showyear = 0;
        $showactivityname = 1;
        $ws = new CRIS_activities();

        try {
            $activityArray = $ws->by_id($this->id);
        } catch (Exception $ex) {
            return;
        }

        if (!count($activityArray)) {
            $output = '<p>' . __('Es wurden leider keine Aktivitäten gefunden.', 'fau-cris') . '</p>';
            return $output;
        }

        $output = $this->make_list($activityArray, $showname, $showyear, $showactivityname);

        return $output;
    }

    /* =========================================================================
     * Private Functions
      ======================================================================== */

    /*
     * Holt Daten vom Webservice je nach definierter Einheit.
     */

    private function fetch_activities($year = '', $start = '', $type = '') {
        $filter = Tools::activity_filter($year, $start, $type);

        $ws = new CRIS_activities();
        $activityArray = array();

        try {
            if ($this->einheit === "orga") {
                $activityArray = $ws->by_orga_id($this->id, $filter);
            }
            if ($this->einheit === "person") {
                $activityArray = $ws->by_pers_id($this->id, $filter);
            }
        } catch (Exception $ex) {
            $activityArray = array();
        }
        return $activityArray;
    }

    /*
     * Ausgabe der Patents
     */

    private function make_list($activities, $name = 1, $year = 1, $activityname = 1, $showtype = 1) {
        if ($this->einheit == "activity") {
            $activitylist = "<div class=\"cris-activities\">";
        } else {
            $activitylist = "<ul class=\"cris-activities\">";
        }

        foreach ($activities as $activity) {
            $activity = (array) $activity;
            foreach ($activity['attributes'] as $attribut => $v) {
                $activity[$attribut] = $v;
            }
            unset($activity['attributes']);
            $names = explode("|", $activity['exportnames']);
            $nameIDs = explode(",", $activity['relpersid']);
            foreach ($nameIDs as $i => $key) {
                $namesArray[] = array('id' => $key, 'name' => $names[$i]);
            }
            $namesList = array();
            foreach ($namesArray as $persname) {
                $name_elements = explode(":", $persname['name']);
                $firstname = $name_elements[1];
                $lastname = $name_elements[0];
                $namesList[] = Tools::get_person_link($persname['id'], $firstname, $lastname, $this->cris_activities_link, $this->cms, $this->pathPersonenseiteUnivis, $this->univis, 0);
            }
            $names_html = implode(", ", $namesList);

            $activity_id = $activity['ID'];
            $activity_type = Tools::getName('activities', $activity['type of activity'], get_locale());
            $lang = strpos(get_locale(), 'de') === 0 ? 'de' : 'en';
            setlocale(LC_TIME, get_locale());

            switch ($activity_type) {
                case "FAU-interne Gremienmitgliedschaft / Funktion":
                    $activity_name = $activity['description function'];
                    $activity_detail = '';
                    $activity_nameofshow = '';
                    $activity_eventname = '';
                    $activity_startdate = $activity['mandate start'];
                    $activity_enddate = $activity['mandate end'];
                    $activity_date = Tools::make_date($activity_startdate, $activity_enddate);
                    $activity_url = $activity['url'];
                    $activity_location = $activity['mirror_orga'];
                    break;
                case "Organisation einer Tagung / Konferenz":
                    $activity_name = $activity['nameconference'];
                    $activity_detail = '';
                    $activity_nameofshow = '';
                    $activity_eventname = '';
                    $activity_startdate = $activity['start date'];
                    $activity_enddate = $activity['end date'];
                    $activity_date = Tools::make_date($activity_startdate, $activity_enddate);
                    $activity_url = $activity['url'];
                    $activity_location = $activity['city'];
                    break;
                case "Herausgeberschaft":
                    $activity_name = $activity['namejournal'];
                    $activity_detail = $activity['role of editorship'];
                    $activity_nameofshow = '';
                    $activity_eventname = '';
                    $activity_startdate = $activity['start date'];
                    $activity_enddate = $activity['end date'];
                    $activity_date = Tools::make_date($activity_startdate, $activity_enddate);
                    $activity_url = $activity['url'];
                    $activity_location = '';
                    break;
                case "Gutachtertätigkeit für eine wissenschaftliche Zeitschrift":
                    $activity_name = $activity['namejournal'];
                    $activity_detail = '';
                    $activity_nameofshow = '';
                    $activity_eventname = '';
                    $activity_startdate = $activity['start date'];
                    $activity_enddate = $activity['end date'];
                    $activity_date = Tools::make_date($activity_startdate, $activity_enddate);
                    $activity_url = $activity['url'];
                    $activity_location = '';
                    break;
                case "Gutachtertätigkeit für eine Förderorganisation":
                    $activity_name = $activity['type of expert activity'];
                    $activity_detail = $activity['mirror_fund'];
                    $activity_nameofshow = '';
                    $activity_eventname = '';
                    $activity_startdate = $activity['start date'];
                    $activity_enddate = $activity['end date'];
                    $activity_date = Tools::make_date($activity_startdate, $activity_enddate);
                    $activity_url = $activity['url'];
                    $activity_location = '';
                    break;
                case "Sonstige FAU-externe Gutachtertätigkeit":
                    $activity_name = $activity['type of expert activity'];
                    $activity_detail = '';
                    $activity_nameofshow = '';
                    $activity_eventname = '';
                    $activity_startdate = $activity['start date'];
                    $activity_enddate = $activity['end date'];
                    $activity_date = Tools::make_date($activity_startdate, $activity_enddate);
                    $activity_url = $activity['url'];
                    $activity_location = $activity['mirror_eorg'];
                    break;
                case "DFG-Fachkollegiat/in":
                    $activity_name = $activity['mirror_dfgfach'];
                    $activity_detail = '';
                    $activity_nameofshow = '';
                    $activity_eventname = '';
                    $activity_startdate = $activity['mandate start'];
                    $activity_enddate = $activity['mandate end'];
                    $activity_date = Tools::make_date($activity_startdate, $activity_enddate);
                    $activity_url = $activity['url'];
                    $activity_location = '';
                    break;
                case "Gremiumsmitglied im Wissenschaftsrat":
                    $activity_name = $activity['description function'];
                    $activity_detail = $activity['memberscicouncil'];
                    $activity_nameofshow = '';
                    $activity_eventname = '';
                    $activity_startdate = $activity['mandate start'];
                    $activity_enddate = $activity['mandate end'];
                    $activity_date = $activity_startdate . " - " . $activity_enddate;
                    $activity_url = $activity['url'];
                    $activity_location = $activity['mirror_orga'];
                    break;
                case "Vortrag":
                    $activity_name = $activity['name'];
                    $activity_detail = '';
                    $activity_nameofshow = '';
                    $activity_eventname = $activity['event name'];
                    $activity_date = $activity['date'];
                    if ($activity_date != '')
                        $activity_date = strftime('%x', strtotime($activity_date));
                    $activity_url = $activity['url'];
                    $activity_location = $activity['mirror_eorg'];
                    break;
                case "Radio- / Fernsehbeitrag / Podcast":
                    $activity_name = $activity['name of contribution'];
                    $activity_detail = '';
                    $activity_nameofshow = $activity['showname'];
                    $activity_eventname = '';
                    $activity_date = $activity['date'];
                    if ($activity_date != '')
                        $activity_date = strftime('%x', strtotime($activity_date));
                    $activity_url = $activity['url'];
                    $activity_location = '';
                    break;
                case "Sonstige FAU-externe Aktivität":
                    $activity_name = $activity['type of extern expert activity'];
                    $activity_detail = '';
                    $activity_nameofshow = '';
                    $activity_eventname = '';
                    $activity_startdate = $activity['start date'];
                    $activity_enddate = $activity['end date'];
                    $activity_date = Tools::make_date($activity_startdate, $activity_enddate);
                    $activity_url = $activity['url'];
                    $activity_location = $activity['mirror_eorg'];
                    break;
            }

            if ($this->einheit != "activity")
                $activitylist .= "<li>";

            if ($name == 1 && !empty($names_html))
                $activitylist .= $names_html . ": ";
            if (!empty($activity_type) & $showtype != 0)
                $activitylist .= $activity_type;
            if (!empty($activity_name))
                $activitylist .= " <strong>\"<a href=\"https://cris.fau.de/converis/publicweb/activity/" . $activity_id . "\" target=\"blank\" title=\"" . __('Detailansicht auf cris.fau.de in neuem Fenster &ouml;ffnen', 'fau-cris') . "\">" . $activity_name . "</a>\"</strong>";
            if (!empty($activity_detail))
                $activitylist .= " (" . $activity_detail . ")";
            if (!empty($activity_date))
                $activitylist .= " (" . $activity_date . ")";
            if (!empty($activity_eventname))
                $activitylist .= ", " . __('Veranstaltung', 'cris-fau') . ": " . $activity_eventname;
            if (!empty($activity_nameofshow))
                $activitylist .= ", " . __('In', 'cris-fau') . ": \"" . $activity_nameofshow . "\"";
            if (!empty($activity_location))
                $activitylist .= ", " . $activity_location;
            if (!empty($activity_url))
                $activitylist .= ", URL: <a href=\"" . $activity_url . "\" target=\"blank\" title=\"" . __('Link in neuem Fenster &ouml;ffnen', 'fau-cris') . "\">" . $activity_url . "</a>";

            if ($this->einheit != "activity")
                $activitylist .= "</li>";
        }

        if ($this->einheit == "activity") {
            $activitylist .= "</div>";
        } else {
            $activitylist .= "</ul>";
        }

        return $activitylist;
    }

}

class CRIS_activities extends CRIS_webservice {
    /*
     * actients/grants requests
     */

    public function by_orga_id($orgaID = null, &$filter = null) {
        if ($orgaID === null || $orgaID === "0")
            throw new Exception('Please supply valid organisation ID');

        if (!is_array($orgaID))
            $orgaID = array($orgaID);

        $requests = array();
        foreach ($orgaID as $_o) {
            $requests[] = sprintf("getrelated/Organisation/%d/acti_has_orga", $_o);
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
            $requests[] = sprintf('getrelated/Person/%d/acti_has_pers', $_p);
        }
        return $this->retrieve($requests, $filter);
    }

    public function by_id($awarID = null) {
        if ($awarID === null || $awarID === "0")
            throw new Exception('Please supply valid activity ID');

        if (!is_array($awarID))
            $awarID = array($awarID);

        $requests = array();
        foreach ($awarID as $_p) {
            $requests[] = sprintf('get/Activity/%d', $_p);
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

        $activities = array();

        foreach ($data as $_d) {
            foreach ($_d as $activity) {
                $a = new CRIS_activity($activity);
                if ($a->ID) {
                    if (!empty($a->attributes['date'])) {
                        $a->attributes['year'] = substr($a->attributes['date'], 0, 4);
                        $a->attributes['sortdate'] = $a->attributes['date'];
                    } elseif (!empty($a->attributes['start date'])) {
                        $a->attributes['year'] = substr($a->attributes['start date'], 0, 4);
                        $a->attributes['sortdate'] = $a->attributes['start date'];
                    } elseif (!empty($a->attributes['mandate start'])) {
                        $a->attributes['year'] = substr($a->attributes['mandate start'], 0, 4);
                        $a->attributes['sortdate'] = $a->attributes['mandate start'];
                    }
                    if (!empty($a->attributes['sortdate'])) {
                        $a->attributes['year'] = substr($a->attributes['sortdate'], 0, 4);
                    }
                }
                if ($a->ID && ($filter === null || $filter->evaluate($a)))
                    $activities[$a->ID] = $a;
            }
        }

        return $activities;
    }

}

class CRIS_activity extends CRIS_Entity {
    /*
     * object for single activity
     */

    function __construct($data) {
        parent::__construct($data);
    }

}
