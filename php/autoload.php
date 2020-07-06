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

use Arnapou\SimpleSite\Core\PhpCode;
use Arnapou\SimpleSite\Core\ServiceContainer;

return new class() implements PhpCode {
    public function init(ServiceContainer $container): void
    {
        spl_autoload_register(
            static function ($class) {
                $filename = __DIR__ . '/../src/' . str_replace('\\', '/', $class) . '.php';
                if (is_file($filename)) {
                    require $filename;
                }
            }
        );
    }
};
