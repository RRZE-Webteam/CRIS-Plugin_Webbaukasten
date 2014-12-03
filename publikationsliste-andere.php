<?php
require_once("class_Publikationsliste.php");

include('cache-top.php');

$liste = new Publikationsliste();
$liste->publikationstypen("Other");

include('cache-bottom.php');