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
        if (!function_exists("_x")) {
            function _x($text, $x=null, $y=null) {
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
            'Pfad_Personenseite' => 'mitarbeiter',
            'Pfad_Personenseite_Univis' => '/wir-ueber-uns/mitarbeiter/mitarbeiter.shtml',
            'cris_cache' => '18000',
            'Sprache' => 'de_DE',
            'Reihenfolge_Publikationen' => array(
                'buecher',
                'zeitschriftenartikel',
                'sammelbandbeitraege',
                'herausgeberschaften',
                'konferenzbeitraege',
                'uebersetzungen',
                'abschlussarbeiten',
                'andere'
            ),
            'Personeninfo_Univis' => 'none',
            'BibTex' => '1',
            'Reihenfolge_Auszeichnungen' => array(
                'preise',
                'mitgliedschaften',
                'stipendien',
                'mitgliedschaften',
                'andere'
                ),
            'Personeninfo_Univis_Auszeichnungen' => 'none',
            'Reihenfolge_Projekte' => array(
                'einzelfoerderung',
                'teilprojekt',
                'gesamtprojekt',
                'graduiertenkolleg',
                'eigenmittel'
            ),
            'Personeninfo_Univis_Projekte' => 'none',
            'Reihenfolge_Patente' => array(
                'patentanmeldung',
                'gebrauchsmuster',
                'schutzrecht',
                'nachanmeldung',
                'nationalisierung',
                'validierung'
            ),
            'Personeninfo_Univis_Patente' => 'none',
            'Reihenfolge_Aktivitaeten' => array(
                'organisation_konferenz',
                'fau-gremienmitgliedschaft',
                'herausgeberschaft',
                'gutachter_zeitschrift',
                'gutachter_organisation',
                'gutachter_sonstige',
                'dfg-fachkollegiat',
                'mitglied_wissenschaftsrat',
                'vortrag',
                'medien',
                'sonstige'
            ),
            'Personeninfo_Univis_Aktivitaeten' => 'none',
        );

        // load options
        $fpath = '../../cris.conf';
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

        // fit WBK keys to WP keys
        $options["cris_org_nr"] = $options["CRISOrgNr"];
        unset($options["CRISOrgNr"]);

        $options["cris_univis"] = $options["Personeninfo_Univis"];
        unset($options["Personeninfo_Univis"]);

        $options["cris_pub_order"] = (!is_array($options["Reihenfolge_Publikationen"]) ? explode("|", $options['Reihenfolge_Publikationen']) : $options['Reihenfolge_Publikationen']);
        unset($options["Reihenfolge_Publikationen"]);

        $options["cris_bibtex"] = $options["BibTex"];
        unset($options["BibTex"]);

        $options["cris_award_link"] = $options["Personeninfo_Univis_Auszeichnungen"];
        unset($options["Personeninfo_Univis_Auszeichnungen"]);

        $options["cris_award_order"] = (!is_array($options["Reihenfolge_Auszeichnungen"]) ? explode("|", $options['Reihenfolge_Auszeichnungen']) : $options['Reihenfolge_Auszeichnungen']);
        unset($options["Reihenfolge_Auszeichnungen"]);

        $options["cris_project_order"] = (!is_array($options["Reihenfolge_Projekte"]) ? explode("|", $options['Reihenfolge_Projekte']) : $options['Reihenfolge_Projekte']);
        unset($options["Reihenfolge_Projekte"]);

        $options["cris_project_link"] = $options["Personeninfo_Univis_Projekte"];
        unset($options["Personeninfo_Univis_Projekte"]);

        $options["cris_patent_order"] = (!is_array($options["Reihenfolge_Patente"]) ? explode("|", $options['Reihenfolge_Patente']) : $options['Reihenfolge_Patente']);
        unset($options["Reihenfolge_Patente"]);

        $options["cris_patent_link"] = $options["Personeninfo_Univis_Patente"];
        unset($options["Personeninfo_Univis_Patente"]);

        $options["cris_activities_order"] = (!is_array($options["Reihenfolge_Aktivitaeten"]) ? explode("|", $options['Reihenfolge_Aktivitaeten']) : $options['Reihenfolge_Aktivitaeten']);
        unset($options["Reihenfolge_Aktivitaeten"]);

        $options["cris_activities_link"] =  $options["Personeninfo_Univis_Aktivitaeten"];
        unset($options["Personeninfo_Univis_Aktivitaeten"]);

        return $options;
    }

}
