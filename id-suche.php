<?php

	$ID = $_REQUEST['fau-org-nr'];
	$suchstring = "http://avedas-neu.zuv.uni-erlangen.de/converis/ws/public/infoobject/findsimple/Organisation/FAU_Org_Nr/" . $ID;
	$xml = simplexml_load_file($suchstring);
	$converis_id = $xml->infoObject['id'];

	echo "<form accept-charset='utf-8' method='get' action='" . $_SERVER['PHP_SELF'] . "'>";
	echo "<fieldset><div class='abstand'>";
	echo "<div class='zeile'><label for='fau-org-nr'>FAU-Org-Nr.</label>\n
			<input type='text' value='' name='fau-org-nr'>&nbsp;&nbsp;&nbsp;<input id='submit' type='submit' value='Absenden' name='submit'></div>";
	echo "</div></fieldset>";
	echo "</form>";

	echo "<p>Ihre Converis-ID: <strong>" . $converis_id . "</strong></p>";
	//echo ($converis_id ? "<p>Ihre Converis-ID: <strong>" . $converis_id . "</strong></p>" : '');


