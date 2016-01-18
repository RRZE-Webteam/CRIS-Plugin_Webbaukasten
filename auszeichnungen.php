<?php

require_once("class_Tools.php");
require_once("class_Dicts.php");

include('cache-top.php');

$show = isset($_GET['show']) ? $_GET['show'] : CRIS_Dicts::$defaults['show'];
$orderby = isset($_GET['orderby']) ? $_GET['orderby'] : CRIS_Dicts::$defaults['orderby'];
$pubtype = isset($_GET['pubtype']) ? $_GET['pubtype'] : CRIS_Dicts::$defaults['pubtype'];
$type = isset($_GET['type']) ? $_GET['type'] : CRIS_Dicts::$defaults['type'];
$year = isset($_GET['year']) ? $_GET['year'] : CRIS_Dicts::$defaults['year'];
$start = isset($_GET['start']) ? $_GET['start'] : CRIS_Dicts::$defaults['start'];
if (isset($_GET['orga'])) {
	$orgid = $_GET['orga'];
} elseif (isset($_GET['orgid'])) {
	$orgid = $_GET['orgid'];
} else {
	$orgid = CRIS_Dicts::$defaults['orgid'];
}
if (isset($_GET['person'])) {
	$orgid = $_GET['person'];
} elseif (isset($_GET['persid'])) {
	$orgid = $_GET['persid'];
} else {
	$orgid = CRIS_Dicts::$defaults['persid'];
}
$publication = isset($_GET['publication']) ? $_GET['publication'] : CRIS_Dicts::$defaults['publication'];
$award = isset($_GET['award']) ? $_GET['award'] : CRIS_Dicts::$defaults['award'];
$showname = isset($_GET['showname']) ? $_GET['showname'] : CRIS_Dicts::$defaults['showname'];
$showyear = isset($_GET['showyear']) ? $_GET['showyear'] : CRIS_Dicts::$defaults['showyear'];
$display = isset($_GET['display']) ? $_GET['display'] : CRIS_Dicts::$defaults['display'];

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

require_once('class_Awards.php');
$liste = new Awards($param1, $param2, $display);
if (isset($orderby) && $orderby == 'type') {
	$output = $liste->awardsNachTyp($year, $start, $type, $showname, $showyear, $display);
} elseif (isset($orderby) && $orderby == 'year') {
	$output = $liste->awardsNachJahr($year, $start, $type, $showname, $showyear, $display);
} elseif (isset($award) && $award != '') {
	$output = $liste->singleAward($showname, $showyear, $display);
} else {
	$output = $liste->awardsListe($year, $start, $type, $showname, $showyear, $display);
}

echo $output;

include('cache-bottom.php');
