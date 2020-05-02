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

use Arnapou\SimpleSite\Controllers\StaticController;
use Symfony\Component\Yaml\Yaml;

return new class() extends StaticController {
    protected function yamlContext(string $view): array
    {
        $context = parent::yamlContext($view);

        if ($context['list-posts'] ?? false) {
            $path    = \dirname($this->container()->Config()->path_public() . "/$view");
            $context = array_merge($context, ['posts' => $this->getListPosts($path)]);
        }

        return $context;
    }

    private function getListPosts(string $dir): array
    {
        $contexts = $this->getSubfolderContexts($dir);
        $posts    = [];
        foreach ($contexts as $folder => $context) {
            if (isset($context['post'])) {
                $posts[] = $context['post'] + ['baseurl' => basename($folder)];
            }
        }
        usort(
            $posts,
            function ($a, $b) {
                return (($b['month'] ?? 0) <=> ($a['month'] ?? 0))
                    ?: (($b['title'] ?? 0) <=> ($a['title'] ?? 0));
            }
        );
        return $posts;
    }

    private function getSubfolderContexts(string $dir): array
    {
        $contexts = [];
        $folders  = glob("$dir/*", GLOB_ONLYDIR | GLOB_NOSORT) ?: [];
        foreach ($folders as $folder) {
            if (is_file("$folder/index.yaml")) {
                $contexts[$folder] = Yaml::parseFile("$folder/index.yaml");
            }
        }
        return $contexts;
    }
};
