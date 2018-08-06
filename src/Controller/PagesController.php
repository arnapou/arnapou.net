<?php


namespace App\Controller;

use App\Service\PageService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PagesController extends Controller
{
    /**
     * @var PageService
     */
    private $pageService;

    /**
     * PagesController constructor.
     * @param PageService $pageService
     */
    public function __construct(PageService $pageService)
    {
        $this->pageService = $pageService;
    }

    /**
     *
     * @Route(path="/", name="home", defaults={"page": "index", "trailingSlash": ""})
     * @Route(path="/{page}{trailingSlash}", requirements={"page": "[a-z0-9_-]+(/[a-z0-9_-]+)*", "trailingSlash": "/?"})
     * @param $page
     * @return Response
     */
    public function anyPage($page, $trailingSlash)
    {
        if (empty($trailingSlash) && $this->pageService->isDir($page)) {
            return new RedirectResponse("/$page/");
        }

        $pageFound = null;
        if ($this->pageService->isFile("$page.html.twig")) {
            $pageFound = "$page.html.twig";
        } elseif ($this->pageService->isDir($page) && $this->pageService->isFile("$page/index.html.twig")) {
            $pageFound = "$page/index.html.twig";
        }

        if ($pageFound) {
            $context  = $this->pageService->getContext($pageFound);
            $httpCode = 200;
        } else {
            $pageFound = '404.html.twig';
            $context   = [];
            $httpCode  = 404;
        }

        return $this->render($pageFound, $context, new Response('', $httpCode));
    }
}
