<?php

/**
 * Plugin Name: CRIS-Plugin für Webbaukasten
 * Description: Anzeige von Daten aus dem FAU-Forschungsportal CRIS in Webbaukasten-Seiten
 * Version: 1.1
 * Author: Barbara Bothe
 * Author URI: http://blogs.fau.de/webworking/
 * License: GPLv2 or later
*/
/*
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

class CRIS {

	public function __construct() {
		$this->ladeConf();
	}

	private function ladeConf($args=NULL){
		$options= array();

		// defaults
		$defaults = array(
			'CRISOrgNr'				=> '0',
			'Zeige_Publikationen'	=> '1',
			'Reihenfolge_Publikationen'		=>	array(
										'Journal article',
										'Article in edited volumes',
										'Translation',
										'Book',
										'Editorial',
										'Conference Contribution',
										'Thesis',
										'Other'
									),
			'cris_staff_page'		=>	'mitarbeiter',
//			'Zeige_Auszeichnungen'	=>	'0',
			'cris_cache'			=>	'18000',
			'cris_ignore'			=>	array( 'FoDa-Administrator/in', 'Andere' )
		);

		// load options
		if ($fpath == NULL) {
			$fpath = '../../cris.conf';
		}
		$fpath_alternative = $_SERVER["DOCUMENT_ROOT"].'/vkdaten/cris.conf';

		if(file_exists($fpath_alternative)){ $fpath = $fpath_alternative; }

		$fh = fopen($fpath, 'r') or die('Cannot open file!');
		while(!feof($fh)) {
			$line = fgets($fh);
			$line = trim($line);
			if((strlen($line) == 0) || (substr($line, 0, 1) == '#')) {
				continue; // ignore comments and empty rows
			}
			$arr_opts = preg_split('/\t/', $line); // tab separated
			$options[$arr_opts[0]] = $arr_opts[1];
		}
		fclose($fh);

		// merge defaults with options
		$this->options = array_merge($defaults, $options);
		if($args) {
			$this->options = array_merge($this->options, $args);
		}
	}
}