<?php

use Arnapou\SimpleSite\Core\Controller;
use Symfony\Component\HttpFoundation\Response;

return new class() extends Controller {
    public function configure(): void
    {
        $this->addRoute('ip', [$this, 'routeIpTxt'], 'ip');
        $this->addRoute('ip.{ext}', [$this, 'routeIpHtm'], 'ip')->setRequirement('ext', '(php|htm|html)');
    }

    public function routeIpHtm($ext)
    {
        $ip   = $_SERVER['REMOTE_ADDR'];
        $host = gethostbyaddr("$ip");
        return new Response("<!DOCTYPE html>
<html>
<title>$ip</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<body>
<p>$ip</p>
<p>$host</p>
</body>
</html>");
    }

    public function routeIpTxt($ext)
    {
        return new Response($_SERVER['REMOTE_ADDR']);
    }
};
