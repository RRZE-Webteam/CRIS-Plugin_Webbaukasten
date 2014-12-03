<?php
require_once("class_Personendetail.php");
require_once("class_Publikationsliste.php");
require_once('class_cris.php');

include('cache-top.php');

$getoptions = new CRIS();
$options = $getoptions->options;

$detail = new Personendetail();
$detail->detail();

if ($options['Zeige_Auszeichnungen'] == '1') {
	$detail->auszeichnungen();
}

if ($options['Zeige_Publikationen'] == '1') {
	echo "<h3>Publikationen</h3>";
	$liste = new Publikationsliste();
	$liste->liste();
}

include('cache-bottom.php');