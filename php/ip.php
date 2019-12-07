<?php

use Arnapou\SimpleSite\Core\Controller;
use Symfony\Component\HttpFoundation\Response;

return new class() extends Controller {
    public function configure(): void
    {
        $this->addRoute('ip{ext}', [$this, 'routeIP'], 'ip')->setRequirement('ext', '(\.[a-z]+)?');
    }

    public function routeIP($ext)
    {
        return new Response($_SERVER['REMOTE_ADDR']);
    }
};
