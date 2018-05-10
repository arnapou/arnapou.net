<?php
$image = new PHPImage('php.gif');
$mask = new PHPImage('mask.png');
$image->mask($mask);
$mask->destroy();
$image->format = 'png';
$image->display();