<?php

use Arnapou\SimpleSite\Core\Controller;

return new class() extends Controller {
    public function configure(): void
    {
        $this->addRoute('', [$this, 'routeHome'], 'home');
    }

    public function routeHome()
    {
        return $this->render('index.twig');
    }
};
