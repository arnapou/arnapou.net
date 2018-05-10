<?php


namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Yaml;

class PagesController extends Controller
{

    /**
     *
     * @Route(path="/", name="home")
     */
    public function home()
    {
        return $this->render("index.html.twig", $this->getContext("index"));
    }

    /**
     *
     * @Route(path="/{page}{trailingSlash}", requirements={"page": "[a-z0-9_-]+(/[a-z0-9_-]+)*", "trailingSlash": "/?"})
     * @param $page
     * @return Response
     */
    public function anyPage($page, $trailingSlash)
    {
        if ($trailingSlash) {
            if ($this->pageExists("$page/index.html.twig")) {
                return $this->renderPage("$page/index.html.twig");
            }
        } else {
            if ($this->pageExists("$page.html.twig")) {
                return $this->renderPage("$page.html.twig");
            } elseif ($this->pageIsDirectory($page)) {
                return new RedirectResponse("/$page/");
            }
        }
        return $this->renderPage("404.html.twig");
    }

    /**
     * @param string $name
     * @param int    $code
     * @return Response
     */
    private function renderPage($name, $code = 200)
    {
        $context = $this->getContext(str_replace('html.twig', 'yaml', $name));
        if ($context['list-posts'] ?? false) {
            $context = array_merge($context, ['posts' => $this->getListPosts(dirname($name))]);
        }
        return $this->render($name, $context, $code == 200 ? null : new Response('', $code));
    }

    /**
     * @param $dir
     * @return array
     */
    private function getListPosts($dir)
    {
        $postFolders = glob($this->getPathPages() . "/$dir/*", GLOB_ONLYDIR | GLOB_NOSORT);
        if (!is_array($postFolders)) {
            return [];
        }
        $posts = [];
        foreach ($postFolders as $folder) {
            if (is_file("$folder/index.yaml")) {
                $data = Yaml::parseFile("$folder/index.yaml");
                if (isset($data['post'])) {
                    $posts[] = $data['post'] + ['baseurl' => basename($folder)];
                }
            }
        }
        usort($posts, function ($a, $b) {
            return (($b['month'] ?? 0) <=> ($a['month'] ?? 0))
                ?: (($b['title'] ?? 0) <=> ($a['title'] ?? 0));
        });
        return $posts;
    }

    /**
     * @param $name
     * @return array
     */
    private function getContext($name)
    {
        if (is_file($this->getPathPages() . "/$name")) {
            return Yaml::parseFile($this->getPathPages() . "/$name");
        }
        return [];
    }

    /**
     * @param $name
     * @return bool
     */
    private function pageExists($name)
    {
        return is_file($this->getPathPages() . "/$name");
    }

    /**
     * @param $name
     * @return bool
     */
    private function pageIsDirectory($name)
    {
        return is_dir($this->getPathPages() . "/$name");
    }

    /**
     * @return string
     */
    private function getPathPages()
    {
        return __DIR__ . "/../../templates/pages";
    }

}