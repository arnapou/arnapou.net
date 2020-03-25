<?php

use Arnapou\SimpleSite\Core\Controller;
use Symfony\Component\HttpFoundation\Response;

return new class() extends Controller {
    public function configure(): void
    {
        $this->addRoute('ip', [$this, 'routeIpTxt'], 'ip');
        $this->addRoute('ip.{ext}', [$this, 'routeIpHtm'], 'ip')->setRequirement('ext', '(php|htm|html)');
    }

    public function ipDescription($ip, $host)
    {
        if (strpos($host, '.mobile.')) {
            return 'connexion 4G';
        }
        if (strpos($host, '.wanadoo.')) {
            return 'connexion ADSL';
        }
        return '';
    }

    public function routeIpHtm($ext)
    {
        $ip   = $_SERVER['REMOTE_ADDR'];
        $host = gethostbyaddr("$ip");
        $desc = $this->ipDescription($ip, $host);
        return new Response("<!DOCTYPE html>
<html>
<title>$ip</title>
<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
<style>
body {
    text-align:center;
    font-family: Arial, Helvetica, sans-serif;
}
</style>
<body>
<p>$ip</p>
<p>$host</p>
<p>$desc</p>
</body>
</html>");
    }

    public function routeIpTxt()
    {
        return new Response($_SERVER['REMOTE_ADDR']);
    }
};
