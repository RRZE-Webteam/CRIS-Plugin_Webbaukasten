<?php

require_once("class_Tools.php");

include('cache-top.php');

// Get-Parameter auslesen
$show = isset($_GET['show']) ? $_GET['show'] : CRIS_Dicts::$defaults['show'];
$orderby = isset($_GET['orderby']) ? $_GET['orderby'] : CRIS_Dicts::$defaults['orderby'];
$year = isset($_GET['year']) ? $_GET['year'] : CRIS_Dicts::$defaults['year'];
$start = isset($_GET['start']) ? $_GET['start'] : CRIS_Dicts::$defaults['start'];
if (isset($_GET['pubtype'])) {
    $type = $_GET['pubtype'];
} elseif (isset($_GET['type'])) {
    $type = $_GET['type'];
} else {
    $type = '';
}
if (isset($_GET['orga'])) {
    $orgid = $_GET['orga'];
} elseif (isset($_GET['orgid'])) {
    $orgid = $_GET['orgid'];
} else {
    $options = CRIS::ladeConf();
    $orgid = $options['CRISOrgNr'];
}
if (isset($_GET['person'])) {
    $persid = $_GET['person'];
} elseif (isset($_GET['persid'])) {
    $persid = $_GET['persid'];
} else {
    $persid = CRIS_Dicts::$defaults['persid'];
}
$publication = isset($_GET['publication']) ? $_GET['publication'] : CRIS_Dicts::$defaults['publication'];
$quotation = isset($_GET['quotation']) ? $_GET['quotation'] : CRIS_Dicts::$defaults['quotation'];
$sortby = (isset($_GET['sortby']) && in_array($_GET['sortby'], array('created', 'updated'))) ? $_GET['sortby'] : CRIS_Dicts::$defaults['sortby'];
$award = isset($_GET['award']) ? $_GET['award'] : CRIS_Dicts::$defaults['award'];
$awardnameid = isset($_GET['awardnameid']) ? $_GET['awardnameid'] : '';
$showname = isset($_GET['showname']) ? $_GET['showname'] : CRIS_Dicts::$defaults['showname'];
$showyear = isset($_GET['showyear']) ? $_GET['showyear'] : CRIS_Dicts::$defaults['showyear'];
$showawardname = isset($_GET['showawardname']) ? $_GET['showawardname'] : CRIS_Dicts::$defaults['showawardname'];
$display = isset($_GET['display']) ? $_GET['display'] : CRIS_Dicts::$defaults['display'];
$items = isset($_GET['items']) ? $_GET['items'] : CRIS_Dicts::$defaults['items'];
$project = isset($_GET['project']) ? $_GET['project'] : '';
$hide = isset($_GET['hide']) ? $_GET['hide'] : '';
$hide = str_replace(" ", "", $hide);
$hide = explode(",", $hide);
$role = isset($_GET['role']) ? $_GET['role'] : CRIS_Dicts::$defaults['role'];
$patent = isset($_GET['patent']) ? $_GET['patent'] : '';
$activity = isset($_GET['activity']) ? $_GET['activity'] : '';

// Filterkriterien
if (isset($publication) && $publication != '') {
    $param1 = 'publication';
    $param2 = $publication;
} elseif (isset($project) && $project != '') {
    $param1 = 'project';
    $param2 = $project;
} elseif (isset($award) && $award != '') {
    $param1 = 'award';
    $param2 = $award;
} elseif (isset($awardnameid) && $awardnameid != '') {
    $param1 = 'awardnameid';
    $param2 = $awardnameid;
} elseif (isset($patent) && $patent != '') {
    $param1 = 'patent';
    $param2 = $patent;
} elseif (isset($activity) && $activity != '') {
    $param1 = 'activity';
    $param2 = $activity;
} elseif (isset($persid) && $persid != '') {
    $param1 = 'person';
    $param2 = $persid;
} elseif (isset($orgid) && $orgid != '') {
    $param1 = 'orga';
    if (strpos($orgid, ',')) {
        $orgid = str_replace(' ', '', $orgid);
        $orgid = explode(',', $orgid);
    }
    $param2 = $orgid;
} else {
    $param1 = '';
    $param2 = '';
}

// Ausgabe
if (isset($show)) :
    switch ($show) {
        //Aktivitäten
        case 'activities' :
            require_once('class_Aktivitaeten.php');
            $liste = new Aktivitaeten($param1, $param2);
            if ($activity != '') {
                echo $liste->singleActivity($hide);
            } elseif (!empty($items)) {
                echo $liste->projListe($year, $start, $type, $items, $hide, $role);
            } elseif ($orderby == 'type') {
                echo $liste->actiNachTyp($year, $start, $type, $hide);
            } elseif ($orderby == 'year') {
                echo $liste->actiNachJahr($year, $start, $type, $hide);
            } else {
                echo $liste->actiListe($year, $start, $type, $items, $hide);
            }
            break;
        //Patente
        case 'patents' :
            require_once('class_Patente.php');
            $liste = new Patente($param1, $param2);
            if ($project != '') {
                echo $liste->singlePatent($showname, $showyear, $showpatentname);
            } elseif (!empty($items)) {
                echo $liste->patListe($year, $start, $type, $showname, $showyear, $showpatentname);
            } elseif ($orderby == 'type') {
                echo $liste->patNachTyp($year, $start, $type, $showname, $showyear, $showpatentname, $order2);
            } elseif ($orderby == 'year') {
                echo $liste->patNachJahr($year, $start, $type, $showname, $showyear, $showpatentname, $order2);
            } else {
                echo $liste->patListe($year, $start, $type, $showname, $showyear, $showpatentname);
            }
            break;
        //Projekte
        case 'projects' :
            require_once('class_Projekte.php');
            $liste = new Projekte($param1, $param2);
            if ($project != '') {
                echo $liste->singleProj($hide);
            } elseif (!empty($items)) {
                echo $liste->projListe($year, $start, $type, $items, $hide, $role);
            } elseif ($orderby == 'type') {
                echo $liste->projNachTyp($year, $start, $type, $hide, $role);
            } elseif ($orderby == 'year') {
                echo $liste->projNachJahr($year, $start, $type, $hide, $role);
            } else {
                echo $liste->projListe($year, $start, $type, $items, $hide, $role);
            }
            break;
        // Awards
        case 'awards':
            require_once('class_Auszeichnungen.php');
            $liste = new Auszeichnungen($param1, $param2, $display);
            if ($award != '') {
                echo $liste->singleAward($showname, $showyear, $showawardname, $display);
            } elseif ($orderby == 'type') {
                echo $liste->awardsNachTyp($year, $start, $type, $awardnameid, $showname, $showyear, $showawardname, $display);
            } elseif ($orderby == 'year') {
                echo $liste->awardsNachJahr($year, $start, $type, $awardnameid, $showname, 0, $showawardname, $display);
            } else {
                echo $liste->awardsListe($year, $start, $type, $awardnameid, $showname, $showyear, $showawardname, $display);
            }
            break;
        // Publications
        default:
        case 'publications':
            require_once('class_Publikationen.php');
            $liste = new Publikationen($param1, $param2);
            $order1 = 'year';
            $order2 = '';
            if (strpos($orderby, ',') !== false) {
                $orderby = str_replace(' ', '', $orderby);
                $order1 = explode(',', $orderby)[0];
                $order2 = explode(',', $orderby)[1];
            } else {
                $order1 = $orderby;
                $order2 = '';
            }
            if ($publication != '') {
                echo $liste->singlePub($quotation);
            } elseif (!empty($items) || !empty($sortby)) {
                echo $liste->pubListe($year, $start, $type, $quotation, $items, $sortby);
            } elseif ($order1 == 'type' || $orderby == 'pubtype') {
                echo $liste->pubNachTyp($year, $start, $type, $quotation, $order2);
            } else {
                echo $liste->pubNachJahr($year, $start, $type, $quotation, $order2);
            }
            break;
    }
endif;

include('cache-bottom.php');
