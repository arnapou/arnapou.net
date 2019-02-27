<?php

/*
 * This file is part of the arnapou.net site package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (isset($_SERVER['REMOTE_ADDR'])) {
    echo $_SERVER['REMOTE_ADDR'];
}
