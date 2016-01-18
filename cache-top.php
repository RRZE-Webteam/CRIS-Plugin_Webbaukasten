<?php
require_once('class_CRIS.php');

$url = $_SERVER["REQUEST_URI"];
$break = Explode('/', $url);
$file = $break[count($break) - 2] . "-" . $break[count($break) - 1];
$cachefile = 'cache/cached-'. $file;
$getoptions = new CRIS();
$options = $getoptions->options;
$cachetime = $options['Cache_Zeit'];

// Serve from the cache if it is younger than $cachetime
if (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile)) {
    echo "<!-- Cached copy, generated ".date('H:i', filemtime($cachefile))." -->\n";
    include($cachefile);
    exit;
}
ob_start(); // Start the output buffer