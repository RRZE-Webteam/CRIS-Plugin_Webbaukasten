<?php
require_once("class_Mitarbeiterliste.php");

include('cache-top.php');

$liste = new Mitarbeiterliste();
$liste->liste(1);

include('cache-bottom.php');