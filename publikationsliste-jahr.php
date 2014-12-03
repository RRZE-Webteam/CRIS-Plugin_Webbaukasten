<?php
require_once("class_Publikationsliste.php");

include('cache-top.php');

$jahr = substr($_SERVER['PATH_INFO'],1);

$liste = new Publikationsliste();
$liste->publikationsjahre($jahr);

include('cache-bottom.php');