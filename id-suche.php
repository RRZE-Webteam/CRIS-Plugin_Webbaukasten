<?php

$ID = $_REQUEST['fau-org-nr'];
$suchstring = "http://avedas-neu.zuv.uni-erlangen.de/converis/ws/public/infoobject/findsimple/Organisation/FAU_Org_Nr/" . $ID;
$xml = simplexml_load_file($suchstring);
$converis_id = $xml->infoObject['id'];

echo "<p>[Test: Fau-Org 1112110011 -> Converis 141440]</p>";
echo "<form accept-charset='utf-8' method='get' action='" . $_SERVER['PHP_SELF'] . "'>";
echo "<p><label for='fau-org-nr'>FAU-Org-Nr.</label>\n
		<input type='text' value='' name='fau-org-nr'></p>\n";
echo"<p><input id='submit' type='submit' value='Absenden' name='submit'></p>";
echo "</form>";
echo "<p>Ihre Converis-ID: <strong>" . $converis_id . "</strong>";
/*echo "<pre>";
print_r($_REQUEST['fau-org-nr']);
echo "</pre>";*/
echo "</p>";
