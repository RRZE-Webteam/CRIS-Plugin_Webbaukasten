<?php
require_once("class_Dicts.php");

class Tools {

	public static function getAcronym($acadTitle) {
		$acronym = '';
		foreach (explode(' ', $acadTitle) as $actitle) {
			if (array_key_exists($actitle, CRIS_Dicts::$acronyms) && CRIS_Dicts::$acronyms[$actitle] != '') {
				$acronym .= " " . CRIS_Dicts::$acronyms[$actitle];
			}
			$acronym = trim($acronym);
		}
		return $acronym;
	}

	public static function getPubName($pub, $lang) {
		return CRIS_Dicts::$pubNames[$pub][$lang];
	}

	public static function getPubTranslation($pub) {
		foreach (CRIS_Dicts::$pubNames as $pubindex) {
			//print $pub;
			//print_r($pubindex['en']);
			//print_r($pubindex['de']);
			if ($pubindex['de'] == $pub) {
				echo "de";
				return $pubindex['en'];
			} elseif ($pubindex['en'] == $pub) {
				/*echo "en";
				print $pubindex['de'];
				print_r(self::$pubNames[$pubindex]);*/
				return $pubindex['de'];
			}
		}
	}

	public static function XML2obj($xml_url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $xml_url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$xml = curl_exec($ch);
		curl_close($ch);

		libxml_use_internal_errors(true);
		try {
			$xmlTree = new SimpleXMLElement($xml);

		} catch (Exception $e) {
			// Something went wrong.
			$error_message = 'Fehler beim Einlesen der Daten: Bitte überprüfen Sie die CRIS-ID.';
			foreach(libxml_get_errors() as $error_line) {
				$error_message .= "<br>" . $error_line->message;
			}
			trigger_error($error_message);
			return false;
		}
		return $xmlTree;
	}

	/*
	 * Array sortieren
	 */

	public static function record_sortByName($results) {

		// Define the custom sort function
		function custom_sort ($a, $b) { return (strcasecmp ($a['lastName'],$b['lastName']));}
		// Sort the multidimensional array
		uasort($results, "custom_sort");
		return $results;
	}

	public static function record_sortByYear($results) {

		// Define the custom sort function
		function custom_sort_year($a, $b) {
			return $a['publYear'] < $b['publYear'];
		}
		// Sort the multidimensional array
		uasort($results, "custom_sort_year");
		return $results;
	}

	public static function sort_key(&$sort_array, $keys_array) {
		if(empty($sort_array) || !is_array($sort_array) || empty($keys_array)) return;
		if(!is_array($keys_array)) $keys_array = explode(',',$keys_array);
		if(!empty($keys_array)) $keys_array = array_reverse($keys_array);
		foreach($keys_array as $n){
			if(array_key_exists($n, $sort_array)){
				$newarray = array($n=>$sort_array[$n]); //copy the node before unsetting
				unset($sort_array[$n]); //remove the node
				$sort_array = $newarray + array_filter($sort_array); //combine copy with filtered array
			}
		}
		return $sort_array;
	}


	/*
	 * Mehrdimensionales Array nach value sortieren
	 * Quelle: http://php.net/manual/de/function.array-multisort.php#91638
	 */
	public static function array_msort($array, $cols) {
		$colarr = array();
		foreach ($cols as $col => $order) {
			$colarr[$col] = array();
			foreach ($array as $k => $row) { $colarr[$col]['_'.$k] = strtolower($row[$col]); }
		}
		$eval = 'array_multisort(';
		foreach ($cols as $col => $order) {
			$eval .= '$colarr[\''.$col.'\'],'.$order.',';
		}
		$eval = substr($eval,0,-1).');';
		eval($eval);
		$ret = array();
		foreach ($colarr as $col => $arr) {
			foreach ($arr as $k => $v) {
				$k = substr($k,1);
				if (!isset($ret[$k])) $ret[$k] = $array[$k];
				$ret[$k][$col] = $array[$k][$col];
			}
		}
		return $ret;

	}

	/*
	 * Publikationen-Array filtern
	 */

	public static function filter_publications($publications, $year = '', $start = '', $type = '') {

		$publications_filtered = array();
		if (!empty($type)) {
			$pubTyp = Tools::getPubName($type, "en");
			$pubTyp_de = Tools::getPubName($type, "de");
		}
		if (!empty($type) && !isset($pubTyp_de)) {
			return "<p>Falscher Parameter</p>";
		}

		foreach($publications as $id => $book) {
			if(
				(empty($year) || $book['publYear'] == $year) &&
				(empty($start) || $book['publYear'] >= $start) &&
				(empty($type) || $book['Publication type'] == $pubTyp)
			){
				$publications_filtered[$id] = $book;
			}
		}
		return $publications_filtered;
	}

	/*
	 * Awards-Array filtern
	 */

	public static function filter_awards($awards, $year = '', $start = '', $type = '') {

		$awards_filtered = array();
		foreach($awards as $id => $award) {
			if(
				(empty($year) || $award['Year award'] == $year) &&
				(empty($start) || $award['Year award'] >= $start) &&
				(empty($type) || $award['Type of award'] == $type)
			){
				$awards_filtered[$id] = $award;
			}
		}
		return $awards_filtered;
	}

	
}