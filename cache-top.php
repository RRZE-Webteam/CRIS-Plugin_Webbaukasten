<?php
require_once('class_CRIS.php');
require_once('class_Dicts.php');

$url = $_SERVER["REQUEST_URI"];
$break = Explode('/', $url);
$file = $break[count($break) - 2] . "-" . $break[count($break) - 1];
$cachefile = 'cache/cached-'. $file;
new CRIS();
$options = CRIS::ladeConf();
if (array_key_exists('Cache_Zeit', $options)) {
    $cachetime = $options['Cache_Zeit'];
} else {
    $cachetime = 18000;
}

// Serve from the cache if it is younger than $cachetime
if (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile)) {
    echo "<!-- Cached copy, generated ".date('H:i', filemtime($cachefile))." -->\n";
    include($cachefile);
    exit;
}
ob_start(); // Start the output buffer