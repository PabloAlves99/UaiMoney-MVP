<?php
// Local development server only; production uses public/ as its document root.
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$file=realpath(dirname(__DIR__).'/public'.$path);
$root=realpath(dirname(__DIR__).'/public');
if($file && str_starts_with($file,$root.DIRECTORY_SEPARATOR) && is_file($file)) return false;
require dirname(__DIR__).'/public/index.php';
