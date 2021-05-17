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
use Symfony\Component\HttpFoundation\JsonResponse;

return new class() extends Controller {
    private $responseTtl = 300;

    public function configure(): void
    {
        $this->addRoute('weather{days}{city}{ext}', [$this, 'weather'], 'weather')
            ->setRequirement('days', '[2345]?')
            ->setRequirement('city', '(-[a-zA-Z ]+)?')
            ->setRequirement('ext', '(\.json)?');
    }

    public function weather(?string $days = null, ?string $city = null, ?string $ext = null)
    {
        $client = new WeatherApiClient(trim((string) $city, '-'), (int) ($days ?: 5));
        $graphics = new WeatherGraphics($client);

        if ($ext) {
            $response = new JsonResponse($client->request());
        } else {
            $response = $this->render(
                '@templates/weather.twig',
                [
                    'weather' => $graphics,
                    'client' => $client,
                    'days' => $days,
                    'city' => $city,
                    'TTL' => $this->responseTtl + 5,
                ]
            );
        }
        $response->setSharedMaxAge($this->responseTtl);
        $response->setExpires((new DateTime())->modify("+$this->responseTtl seconds"));

        return $response;
    }
};
