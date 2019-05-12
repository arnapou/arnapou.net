<?php

namespace App;

use Cms\Controller\StaticController;
use Symfony\Component\Yaml\Yaml;

new class() extends StaticController {
    protected function renderContext(string $view): array
    {
        $context = parent::renderContext($view);

        if ($context['list-posts'] ?? false) {
            $path    = \dirname($this->site()->Config()->pathPublic() . "/$view");
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
        usort($posts, function ($a, $b) {
            return (($b['month'] ?? 0) <=> ($a['month'] ?? 0))
                ?: (($b['title'] ?? 0) <=> ($a['title'] ?? 0));
        });
        return $posts;
    }

    private function getSubfolderContexts(string $dir): array
    {
        $contexts = [];
        $folders  = glob("$dir/*", GLOB_ONLYDIR | GLOB_NOSORT);
        if (\is_array($folders)) {
            foreach ($folders as $folder) {
                if (is_file("$folder/index.yaml")) {
                    $contexts[$folder] = Yaml::parseFile("$folder/index.yaml");
                }
            }
        }
        return $contexts;
    }
};
