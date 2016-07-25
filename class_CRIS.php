<?php

class CRIS {

    public function __construct() {
        $options = self::ladeConf();
        $locale = $options['Sprache'];

        switch (substr($options['Sprache'], 0, 2)) {
            case 'de':
                $locale = 'de_DE';
                break;
            case 'en':
                $locale = 'en_US';
                break;
            default:
                $locale = $options['Sprache'];
        }
        // Sprache einstellen
        putenv('LC_ALL=' . $locale);
        setlocale(LC_ALL, $locale);
        // Pfads der Übersetzungstabellen
        bindtextdomain("fau-cris", "./languages");
        // Domain auswählen
        textdomain("fau-cris");

        // WP-Funktionen ersetzen
        if (!function_exists("__")) {
            function __($text, $x=null) {
                return _($text);
            }
        }
        if (!function_exists("get_locale")) {
            function get_locale() {
                new CRIS();
                $locale = CRIS::getLocale();
                return $locale;
            }
        }

    }

    public static function getLocale() {
        $options = self::ladeConf();
        $locale = $options['Sprache'];
        return $locale;
    }

    public static function ladeConf($args = NULL) {
        $options = array();

        // defaults
        $defaults = array(
            'CRISOrgNr' => '0',
            'Zeige_Publikationen' => '1',
            'Reihenfolge_Publikationen' => array(
                'Journal article',
                'Article in edited volumes',
                'Translation',
                'Book',
                'Editorial',
                'Conference Contribution',
                'Thesis',
                'Other'
            ),
            'Pfad_Personenseite' => 'mitarbeiter',
            'Personeninfo_Univis' => '',
            'Pfad_Personenseite_Univis' => '/wir-ueber-uns/mitarbeiter/mitarbeiter.shtml',
//			'Zeige_Auszeichnungen'	=>	'0',
            'cris_cache' => '18000',
            'cris_ignore' => array('FoDa-Administrator/in', 'Andere'),
            'Sprache' => 'de_DE',
            'BibTex' => '1',
            'Personeninfo_Univis_Auszeichnungen' => ''
        );

        // load options
        //if ($fpath == NULL) {
            $fpath = '../../cris.conf';
        //}
        $fpath_alternative = $_SERVER["DOCUMENT_ROOT"] . '/vkdaten/cris.conf';

        if (file_exists($fpath_alternative)) {
            $fpath = $fpath_alternative;
        }

        $fh = fopen($fpath, 'r') or die('Cannot open file!');
        while (!feof($fh)) {
            $line = fgets($fh);
            $line = trim($line);
            if ((strlen($line) == 0) || (substr($line, 0, 1) == '#')) {
                continue; // ignore comments and empty rows
            }
            $arr_opts = preg_split('/\t/', $line); // tab separated
            $options[$arr_opts[0]] = $arr_opts[1];
        }
        fclose($fh);

        // get locale from website.conf
        $fpath_web = '../../website.conf';
        $fpath_alternative_web = $_SERVER["DOCUMENT_ROOT"] . '/vkdaten/website.conf';

        if (file_exists($fpath_alternative_web)) {
            $fpath_web = $fpath_alternative_web;
        }

        $fh_web = fopen($fpath_web, 'r') or die('Cannot open file!');
        while (!feof($fh_web)) {
            $line = fgets($fh_web);
            $line = trim($line);
            if (substr($line, 0, 7) == 'Sprache') {
                $arr_opts = preg_split('/\t/', $line); // tab separated
                $options[$arr_opts[0]] = $arr_opts[1];
            }
        }
        fclose($fh_web);

        // merge defaults with options
        $options = array_merge($defaults, $options);
        if ($args) {
            $options = array_merge($options, $args);
        }

        return $options;
    }

}
