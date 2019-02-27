<?php

/*
 * This file is part of the arnapou.net site package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

use Symfony\Component\Yaml\Yaml;

class PageService
{
    /**
     * @param $name
     * @return array
     */
    public function getContext($name)
    {
        $context = [];
        if ($this->isFile($name)) {
            $path    = \dirname($this->getPathTemplates() . "/$name");
            $ctxFile = $this->getPathTemplates() . '/' . str_replace('.html.twig', '.yaml', $name);
            if (is_file($ctxFile)) {
                $context = Yaml::parseFile($ctxFile);
            }

            if ($context['list-posts'] ?? false) {
                $context = array_merge($context, ['posts' => $this->getListPosts($path)]);
            }
        }
        return $context;
    }

    /**
     * @param $dir
     * @return array
     */
    private function getListPosts($dir)
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

    /**
     * @param $dir
     * @return array
     */
    private function getSubfolderContexts($dir)
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

    /**
     * @param $name
     * @return bool
     */
    public function isFile($name)
    {
        if (!preg_match('!^[a-zA-Z0-9_-]+(/[a-zA-Z0-9_-]+)*\.html\.twig$!', $name)) {
            throw new \InvalidArgumentException('name is not valid');
        }
        return is_file($this->getPathTemplates() . "/$name");
    }

    /**
     * @param $name
     * @return bool
     */
    public function isDir($name)
    {
        if (!preg_match('!^[a-zA-Z0-9_-]+(/[a-zA-Z0-9_-]+)*$!', $name)) {
            throw new \InvalidArgumentException('name is not valid');
        }
        return is_dir($this->getPathTemplates() . "/$name");
    }

    /**
     * @return string
     */
    public function getPathTemplates()
    {
        return __DIR__ . '/../../templates/pages';
    }
}
