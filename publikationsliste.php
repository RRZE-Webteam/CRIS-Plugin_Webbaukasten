<?php
require_once("class_Tools.php");
require_once("class_Publikationsliste.php");

include('cache-top.php');

$liste = new Publikationsliste();
if (isset($_SERVER['PATH_INFO'])) {
	$param = substr($_SERVER['PATH_INFO'],1);
	if (is_numeric($param)) {
		$liste->publikationsjahre($param);
	} elseif ($param == 'typ') {
		$liste->pubNachTyp();
	} elseif (substr($param,0,6) === 'start-') {
		$start = explode("-", $param)[1];
		$liste->publikationsjahrestart($start);
	} else {
		$liste->publikationstypen($param);
	}
} else {
	$liste->pubNachJahr();
}

include('cache-bottom.php');