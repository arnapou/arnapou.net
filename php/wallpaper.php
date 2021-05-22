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

use Arnapou\SimpleSite\Core\Controller;

return new class() extends Controller {
    private const RELATIVE_PATH = '/img/wallpaper';

    public function configure(): void
    {
        $this->addRoute('mobile_wallpaper.jpg', [$this, 'wallpaper'], 'mobile_wallpaper');
    }

    public function wallpaper()
    {
        $files = [];
        if (is_dir($path = $this->container()->Config()->path_public() . self::RELATIVE_PATH)) {
            foreach (glob("$path/*.*") ?: [] as $filename) {
                if ('jpg' === strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
                    $files[] = $filename;
                }
            }
        }

        if (empty($files)) {
            return $this->redirect('/img/gouttes.jpg');
        }

        $filename = $files[random_int(0, \count($files) - 1)];

        return $this->redirect(self::RELATIVE_PATH . '/' . basename($filename));
    }
};
