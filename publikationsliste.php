<?php

require_once("class_Tools.php");
require_once("class_Publikationen.php");

include('cache-top.php');

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
$publication = $_GET["publication"];
$quotation = isset($_GET['quotation']) ? $_GET['quotation'] : CRIS_Dicts::$defaults['quotation'];


if (isset($orgid) && $orgid != '') {
	$param1 = 'orga';
	$param2 = $orgid;
} elseif (isset($persid) && $persid != '') {
	$param1 = 'person';
	$param2 = $persid;
} elseif (isset($publication) && $publication !='') {
			$param1 = 'publication';
			$param2 = $publication;
} else {
	$param1 = '';
	$param2 = '';
}

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

echo $output;

include('cache-bottom.php');
