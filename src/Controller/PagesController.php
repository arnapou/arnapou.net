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
                return $this->render("$page/index.html.twig", $this->getContext("$page/index"));
            }
        } else {
            if ($this->pageExists("$page.html.twig")) {
                return $this->render("$page.html.twig", $this->getContext("$page"));
            } elseif ($this->pageIsDirectory($page)) {
                return new RedirectResponse("$page/");
            }
        }
        return $this->render("404.html.twig", $this->getContext("404"), new Response('', 404));
    }

    /**
     * @param $page
     * @return array
     */
    private function getContext($page)
    {
        if (is_file($this->getPathPages() . "/$page.yaml")) {
            return Yaml::parseFile($this->getPathPages() . "/$page.yaml");
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