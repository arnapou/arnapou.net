<?php

declare(strict_types=1);

/*
 * This file is part of the Arnapou www package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Arnapou\SimpleSite\Core\Controller;
use Symfony\Component\HttpFoundation\Response;

return new class() extends Controller {
    public function configure(): void
    {
        $this->addRoute('ip', [$this, 'routeIpTxt'], 'ip-txt');
        $this->addRoute('ip.{ext}', [$this, 'routeIpHtm'], 'ip-htm')->setRequirement('ext', '(php|htm|html)');
    }

    public function ipData()
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $host = gethostbyaddr("$ip");
        $desc = '';
        if (strpos($host, '.mobile.')) {
            $desc = 'connexion 4G';
        }
        if (strpos($host, '.wanadoo.')) {
            $desc = 'connexion ADSL';
        }

        return [
            'ip' => $ip,
            'host' => $host,
            'desc' => $desc,
        ];
    }

    public function routeIpHtm($ext = 'htm')
    {
        $response = $this->render('@templates/ip.twig', $this->ipData());
        $response->setSharedMaxAge(2);
        $response->setExpires((new DateTime())->modify('+2 seconds'));

        return $response;
    }

    public function routeIpTxt()
    {
        $response = new Response(implode("\n", $this->ipData()));
        $response->headers->set('Content-Type', 'text/plain');

        return $response;
    }
};
