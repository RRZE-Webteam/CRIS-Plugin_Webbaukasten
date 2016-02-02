<?php

require_once("class_Tools.php");
require_once("class_Publikationen.php");

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
	$type = CRIS_Dicts::$defaults['type'];
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
	require_once('class_Auszeichnungen.php');
	$liste = new Auszeichnungen($param1, $param2, $display);
	if (isset($orderby) && $orderby == 'type') {
		$output = $liste->awardsNachTyp($year, $start, $type, $showname, $showyear, $display);
	} elseif (isset($orderby) && $orderby == 'year') {
		$output = $liste->awardsNachJahr($year, $start, $type, $showname, $showyear, $display);
	} elseif (isset($award) && $award != '') {
		$output = $liste->singleAward($showname, $showyear, $display);
	} else {
		$output = $liste->awardsListe($year, $start, $type, $showname, $showyear, $display);
	}
} else {
	// Publications
	$liste = new Publikationen($param1, $param2);
	if (isset($orderby) && $orderby == 'type') {
		$output = $liste->pubNachTyp($year, $start, $type, $quotation);
	} elseif (isset($orderby) && $orderby == 'year') {
		$output = $liste->pubNachJahr($year, $start, $type, $quotation);
	} elseif (isset($publication) && $publication != '') {
		$output = $liste->singlePub($quotation);
	} else {
		$output = $liste->pubNachJahr($year, $start, $type, $quotation);
	}
}

echo $output;

include('cache-bottom.php');
