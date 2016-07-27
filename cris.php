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

// Filterkriterien
if (isset($publication) && $publication != '') {
    $param1 = 'publication';
    $param2 = $publication;
} elseif (isset($award) && $award != '') {
    $param1 = 'award';
    $param2 = $award;
} elseif (isset($awardnameid) && $awardnameid != '') {
    $param1 = 'awardnameid';
    $param2 = $awardnameid;
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
if (isset($show) && $show == 'awards') {
    // Awards
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
} else {
    // Publications
    require_once('class_Publikationen.php');
    $liste = new Publikationen($param1, $param2);

    if ($publication != '') {
        echo $liste->singlePub($quotation);
    } elseif (!empty($items) || !empty($sortby)) {
        echo $liste->pubListe($year, $start, $type, $quotation, $items, $sortby);
    } elseif ($orderby == 'type' || $orderby == 'pubtype') {
        echo $liste->pubNachTyp($year, $start, $type, $quotation);
    } else {
        echo $liste->pubNachJahr($year, $start, $type, $quotation);
    }
}

include('cache-bottom.php');
