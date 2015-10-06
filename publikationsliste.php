<?php

require_once("class_Tools.php");
require_once("class_Publikationsliste.php");

include('cache-top.php');

$orderby = $_GET["orderby"];
$pubtype = $_GET["type"];
$year = $_GET["year"];
$start = $_GET["start"];
$orgid = $_GET["orga"];
$persid = $_GET["person"];
$publication = $_GET["publication"];

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
if (isset($year) && $year != '') {
	$filter = 'year';
	$value = $year;
} elseif (isset($start) && $start != '') {
	$filter = 'start';
	$value = $start;
} elseif (isset($pubtype) && $pubtype != '') {
	$filter = 'type';
	$value = $pubtype;
} else {
	$filter = '';
	$value = '';
}

$liste = new Publikationsliste($param1, $param2);

if (isset($orderby) && $orderby == 'type') {
	$output = $liste->pubNachTyp($filter, $value);
} elseif (isset($orderby) && $orderby == 'year') {
	$output = $liste->pubNachJahr($filter, $value);
} elseif (isset($publication) && $publication != '') {
	$output = $liste->singlePub();
} else {
	$output = $liste->pubNachJahr($filter, $value);
}

echo $output;

include('cache-bottom.php');
