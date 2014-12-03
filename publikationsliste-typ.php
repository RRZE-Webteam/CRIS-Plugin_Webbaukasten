<?php
require_once("class_Publikationsliste.php");

include('cache-top.php');

$liste = new Publikationsliste();
$liste->pubNachTyp();

/*echo "<h2>Nur B&uuml;cher:</h2>";
$liste->publikationstypen("Book");

echo "<h2>Nur Buchbeitr&auml;ge:</h2>";
$liste->publikationstypen("Article in Edited Volumes");

echo "<h2>Nur Zeitschriftenartikel:</h2>";
$liste->publikationstypen("Journal article");

echo "<h2>Nur Konferenzbeitr&auml;ge:</h2>";
$liste->publikationstypen("Conference contribution");

echo "<h2>Nur Editorials:</h2>";
$liste->publikationstypen("Editorial");

echo "<h2>Nur Thesis:</h2>";
$liste->publikationstypen("Thesis");

echo "<h2>Nur Andere:</h2>";
$liste->publikationstypen("Other");

echo "<h2>&Uuml;bersetzungen:</h2>";
$liste->publikationstypen("Translation");

//echo "<h2>Alle:</h2>";
//$liste->publikationstypen();*/

include('cache-bottom.php');