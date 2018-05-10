<?php


namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class RedirectedController extends Controller
{

    /**
     *
     * @Route(path="{path}.html", requirements={"path": ".+"})
     * @Route(path="{path}.twig", requirements={"path": ".+"})
     * @Route(path="tech-{path}", requirements={"path": ".+"})
     * @param $path
     * @return RedirectResponse
     */
    public function redirectOldUrls($path)
    {
        $path = rtrim($path, '/');
        return new RedirectResponse("/$path", 301);
    }

}