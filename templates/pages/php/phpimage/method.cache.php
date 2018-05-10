<?php
$image = new PHPImage();
$image->cachetime = 86400*365*10; // 10 years
if($image->cacheok($source, $cached_image)) {
    $image->display($cached_image);
}
else {
    $image->loadfromfile($source);
    // put here your image processing
    // it can be : resize, crop, effects,...
    $image->display($cached_image);
}