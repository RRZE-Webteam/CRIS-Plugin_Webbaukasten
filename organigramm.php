<?php

require_once("class_Mitarbeiterliste.php");

include('cache-top.php');

$liste = new Mitarbeiterliste();
$liste->organigramm();

include('cache-bottom.php');