<?php

namespace App;

use Cms\AbstractController;

new class() extends AbstractController {
    public function configure(): void
    {
        $this->addRoute('', [$this, 'routeHome'], 'home');
    }

    public function routeHome()
    {
        return $this->render('index.twig');
    }
};
