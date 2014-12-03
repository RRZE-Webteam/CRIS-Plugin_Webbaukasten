<?php
require_once("class_Publikationsliste.php");

include('cache-top.php');

$liste = new Publikationsliste();
$liste->pubNachJahr();

include('cache-bottom.php');