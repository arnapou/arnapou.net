<?php
$image = new PHPImage(120, 280);
$image->drawfilledrectangle(5, 5, 35, 35, 'black', 'all(none, 11)');
$image->drawfilledrectangle(45, 5, 75, 35, 'blue', 'all(empty, 11)');
$image->drawfilledrectangle(85, 5, 115, 35, 'red', 'all(biseau, 11)');

$image->drawfilledrectangle(5, 45, 35, 75, 'black', 'all(biseau2, 11)');
$image->drawfilledrectangle(45, 45, 75, 75, 'blue', 'all(biseau3, 11)');
$image->drawfilledrectangle(85, 45, 115, 75, 'red', 'all(biseau4, 11)');

$image->drawfilledrectangle(5, 85, 35, 115, 'black', 'all(round, 11)');
$image->drawfilledrectangle(45, 85, 75, 115, 'blue', 'all(round2, 11)');
$image->drawfilledrectangle(85, 85, 115, 115, 'red', 'all(curve, 11)');

$image->drawfilledrectangle(5, 125, 35, 155, 'black', 'all(curve2, 11)');
$image->drawfilledrectangle(45, 125, 75, 155, 'blue', 'all(curve3, 11)');
$image->drawfilledrectangle(85, 125, 115, 155, 'red', 'all(curve4, 11)');

$image->drawfilledrectangle(5, 165, 35, 195, 'black', 'all(curve5, 11)');
$image->drawfilledrectangle(45, 165, 75, 195, 'blue', 'all(curve6, 11)');
$image->drawfilledrectangle(85, 165, 115, 195, 'red', 'all(trait, 11)');

$image->drawfilledrectangle(5, 205, 35, 235, 'black', 'all(trait2, 11)');
$image->drawfilledrectangle(45, 205, 75, 235, 'blue', 'all(trait3, 11)');
$image->drawfilledrectangle(85, 205, 115, 235, 'red', 'all(round2+curve, 11)');

$image->drawfilledrectangle(5, 245, 35, 275, 'black', 'all(curve5+curve6, 11)');
$image->drawfilledrectangle(45, 245, 75, 275, 'blue', 'all(round%2+round2, 11)');
$image->drawfilledrectangle(85, 245, 115, 275, 'red', 'all(trait2+trait, 11)');
$image->format = 'png';
$image->display();