<?php

namespace App;

use Cms\AbstractController;
use Symfony\Component\HttpFoundation\Response;

new class() extends AbstractController {
    public function configure(): void
    {
        $this->addRoute('ip{ext}', [$this, 'routeIP'], 'ip')->setRequirement('ext', '(\.[a-z]+)?');
    }

    public function routeIP($ext)
    {
        return new Response($_SERVER['REMOTE_ADDR']);
    }
};
