<?php

class CRIS {

	public function __construct() {
		$this->ladeConf();
	}

	private function ladeConf($args=NULL){
		$options= array();

		// defaults
		$defaults = array(
			'CRISOrgNr' => '0',
			'Zeige_Publikationen' => '1',
			'Zeige_Auszeichnungen' => '1',
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