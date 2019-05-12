<?php
include('class.gzip_browser.php');
include('class.jsmin.php');

$gzip = new gzip_browser('uri');
$gzip->exit = true;
$gzip->document_root = './';
$gzip->gz_folder = 'cache/gzip/';
$gzip->send();
?>