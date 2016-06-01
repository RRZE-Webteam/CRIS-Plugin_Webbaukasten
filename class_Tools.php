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
        if (array_key_exists($lang, CRIS_Dicts::$pubNames[$pub])) {
            return CRIS_Dicts::$pubNames[$pub][$lang];
        }
        return CRIS_Dicts::$pubNames[$pub]['en'];
    }

    public static function getpubTitle($pub, $lang) {
        if (array_key_exists($lang, CRIS_Dicts::$pubTitles[$pub])) {
            return CRIS_Dicts::$pubTitles[$pub][$lang];
        }
        if (strpos($lang, 'de_') === 0) {
            return CRIS_Dicts::$pubTitles[$pub]['de_DE'];
        }
        return CRIS_Dicts::$pubTitles[$pub]['en_US'];
    }

    public static function getAwardName($award, $lang) {
        if (array_key_exists($lang, CRIS_Dicts::$awardNames[$award])) {
            return CRIS_Dicts::$awardNames[$award][$lang];
        }
        return CRIS_Dicts::$awardNames[$award]['en'];
    }

    public static function getawardTitle($award, $lang) {
        if (array_key_exists($lang, CRIS_Dicts::$awardTitles[$award])) {
            return CRIS_Dicts::$awardTitles[$award][$lang];
        }
        if (strpos($lang, 'de_') === 0) {
            return CRIS_Dicts::$awardTitles[$award]['de_DE'];
        }
        return CRIS_Dicts::$awardTitles[$award]['en_US'];
    }

    public static function XML2obj($xml_url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $xml_url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $xml = curl_exec($ch);
        curl_close($ch);

        $xmlTree = '';

        libxml_use_internal_errors(true);
        try {
            $xmlTree = new SimpleXMLElement($xml);
        } catch (Exception $e) {
            // Something went wrong.
            print '<p>';
            $error_message = 'Fehler beim Einlesen der Daten: Bitte überprüfen Sie die CRIS-ID.';
            foreach (libxml_get_errors() as $error_line) {
                $error_message .= "<br>" . $error_line->message;
            }
            trigger_error($error_message);
            print '</p>';
            return false;
        }
        return $xmlTree;
    }

    /*
     * Array sortieren
     */

    public static function record_sortByName($results) {

        // Define the custom sort function
        function custom_sort($a, $b) {
            return (strcasecmp($a['lastName'], $b['lastName']));
        }

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

    public static function record_sortByVirtualdate($results) {

        // Define the custom sort function
        function custom_sort_virtualdate($a, $b) {
            return $a['virtualdate'] < $b['virtualdate'];
        }

        // Sort the multidimensional array
        uasort($results, "custom_sort_virtualdate");
        return $results;
    }

    public static function sort_key(&$sort_array, $keys_array) {
        if (empty($sort_array) || !is_array($sort_array) || empty($keys_array))
            return;
        if (!is_array($keys_array))
            $keys_array = explode(',', $keys_array);
        if (!empty($keys_array))
            $keys_array = array_reverse($keys_array);
        foreach ($keys_array as $n) {
            if (array_key_exists($n, $sort_array)) {
                $newarray = array($n => $sort_array[$n]); //copy the node before unsetting
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
            foreach ($array as $k => $row) {
                $colarr[$col]['_' . $k] = strtolower($row[$col]);
            }
        }
        $eval = 'array_multisort(';
        foreach ($cols as $col => $order) {
            $eval .= '$colarr[\'' . $col . '\'],' . $order . ',';
        }
        $eval = substr($eval, 0, -1) . ');';
        eval($eval);
        $ret = array();
        foreach ($colarr as $col => $arr) {
            foreach ($arr as $k => $v) {
                $k = substr($k, 1);
                if (!isset($ret[$k]))
                    $ret[$k] = $array[$k];
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

        foreach ($publications as $id => $book) {
            if (
                    (empty($year) || $book['publYear'] == $year) &&
                    (empty($start) || $book['publYear'] >= $start) &&
                    (empty($type) || $book['Publication type'] == $pubTyp)
            ) {
                $publications_filtered[$id] = $book;
            }
        }
        return $publications_filtered;
    }

    /*
     * Array zur Definition des Filters für Publikationen
     */

    public static function publication_filter($year = '', $start = '', $type = '') {
        $filter = array();
        if ($year !== ''&& $year !== NULL)
            $filter['publyear__eq'] = $year;
        if ($start !== ''&& $start !== NULL)
            $filter['publyear__ge'] = $start;
        if ($type !== '' && $type !== NULL) {
            $pubTyp = Tools::getPubName($type, "en");
            if (empty($pubTyp)) {
                // XXX: hier fehlt eine Übersetzung
                $output .= '<p>Falscher Parameter für Publikationstyp</p>';
                return $output;
            }
            $filter['publication type__eq'] = $pubTyp;
        }
        if (count($filter))
            return $filter;
        return null;
    }

    /*
     * Awards-Array filtern
     */

    public static function filter_awards($awards, $year = '', $start = '', $type = '') {

        $awards_filtered = array();
        foreach ($awards as $id => $award) {
            if (
                    (empty($year) || $award['Year award'] == $year) &&
                    (empty($start) || $award['Year award'] >= $start) &&
                    (empty($type) || $award['Type of award'] == $type)
            ) {
                $awards_filtered[$id] = $award;
            }
        }
        return $awards_filtered;
    }

    /*
     * Array zur Definition des Filters für Awards
     */

    public static function award_filter($year = '', $start = '', $type = '') {
        $filter = array();
        if ($year !== '')
            $filter['year award__eq'] = $year;
        if ($start !== '')
            $filter['year award__ge'] = $start;
        if ($type !== '') {
            $filter['type of award__eq'] = $type;
        }
        if (count($filter))
            return $filter;
        return null;
    }

    /*
     * WP: Anbindung FAU-Person-Plugin
     */

    public static function person_exists($firstname, $lastname) {
        global $wpdb;

        $person = $wpdb->esc_like($firstname) . '%' . $wpdb->esc_like($lastname);
        $sql = "SELECT COUNT(*) FROM $wpdb->posts WHERE post_title LIKE %s AND post_type = 'person'";
        $sql = $wpdb->prepare($sql, $person);
        $person_count = $wpdb->get_var($sql);

        return $person_count;
    }

    public static function person_slug($firstname, $lastname) {
        global $wpdb;

        $person = $wpdb->esc_like($firstname) . '%' . $wpdb->esc_like($lastname);
        $sql = "SELECT post_name FROM $wpdb->posts WHERE post_title LIKE %s AND post_type = 'person'";
        $sql = $wpdb->prepare($sql, $person);
        $person_slug = $wpdb->get_var($sql);

        return $person_slug;
    }

}
