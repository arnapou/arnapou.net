<?php

include __DIR__ . '/../../site.phar';

\Cms\Site::run([
    'name'           => 'Arnapou',
    'path_public'    => __DIR__,
    'path_cache'     => __DIR__ . '/../cache',
    'path_templates' => __DIR__ . '/../templates',
    'path_data'      => __DIR__ . '/../data',
    'path_php'       => [
        __DIR__ . '/../php/init',
        __DIR__ . '/../php/controllers',
    ],
]);
