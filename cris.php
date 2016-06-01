<?php

require_once("class_Tools.php");
require_once("class_Publications.php");

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
    $orgid = CRIS_Dicts::$defaults['orgid'];
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
$award = isset($_GET['award']) ? $_GET['award'] : CRIS_Dicts::$defaults['award'];
$awardnameid = isset($_GET['awardnameid']) ? $_GET['awardnameid'] : '';
$showname = isset($_GET['showname']) ? $_GET['showname'] : CRIS_Dicts::$defaults['showname'];
$showyear = isset($_GET['showyear']) ? $_GET['showyear'] : CRIS_Dicts::$defaults['showyear'];
$display = isset($_GET['display']) ? $_GET['display'] : CRIS_Dicts::$defaults['display'];

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
    $param2 = $orgid;
} else {
    $param1 = '';
    $param2 = '';
}

// Ausgabe
if (isset($show) && $show == 'awards') {
    // Awards
    require_once('class_Auszeichnungen_neu.php');
    $liste = new Auszeichnungen_neu($param1, $param2, $display);

    if ($award != '') {
        echo $liste->singleAward($showname, $showyear, $display);
    } elseif ($orderby == 'type') {
        echo $liste->awardsNachTyp($year, $start, $type, $awardnameid, $showname, $showyear, $display);
    } elseif ($orderby == 'year') {
        echo $liste->awardsNachJahr($year, $start, $type, $awardnameid, $showname, 0, $display);
    } else {
        echo $liste->awardsListe($year, $start, $type, $awardnameid, $showname, $showyear, $display);
    }

} else {
    // Publications
    require_once('class_Publikationen_neu.php');
    $liste = new Publikationen_neu($param1, $param2);

    if ($publication != '') {
        echo $liste->singlePub($quotation);
    } elseif (!empty($items)) {
        echo $liste->pubListe($year, $start, $type, $quotation, $items);
    } elseif ($orderby == 'type' || $orderby == 'pubtype') {
        echo $liste->pubNachTyp($year, $start, $type, $quotation);
    } else {
        echo $liste->pubNachJahr($year, $start, $type, $quotation);
    }
}

include('cache-bottom.php');
