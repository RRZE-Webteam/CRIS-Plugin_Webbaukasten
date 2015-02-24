<?php
require_once("class_Personendetail.php");
require_once("class_Publikationsliste.php");
require_once('class_cris.php');

include('cache-top.php');

$getoptions = new CRIS();
$options = $getoptions->options;

$detail = new Personendetail();
$detail->detail();

include('cache-bottom.php');