<?php

declare(strict_types=1);

/*
 * This file is part of the Arnapou www package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

include __DIR__ . '/../../simplesite.phar';

date_default_timezone_set('Europe/Paris');

\Arnapou\SimpleSite\run(
    [
        'name'           => 'Arnapou',
        'path_public'    => __DIR__,
        'path_cache'     => '/cache/arnapou.net',
        'path_templates' => __DIR__ . '/../templates',
        'path_data'      => __DIR__ . '/../data',
        'path_php'       => __DIR__ . '/../php',
    ]
);
