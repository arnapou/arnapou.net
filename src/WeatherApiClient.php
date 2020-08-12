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

use Symfony\Component\Cache\Adapter\RedisAdapter;

class WeatherApiClient
{
    // Temps courant
    // doc    -> https://openweathermap.org/current
    // ex     -> https://api.openweathermap.org/data/2.5/weather?id=3023606&lang=fr&units=metric&appid=b8818ae6ec5845bc74a39443b5b58748

    // Temps Toutes les 3h sur 5 jours
    // doc    -> https://openweathermap.org/forecast5
    // ex     -> https://api.openweathermap.org/data/2.5/forecast?id=3023606&lang=fr&units=metric&appid=b8818ae6ec5845bc74a39443b5b58748

    // icons  -> https://openweathermap.org/weather-conditions
    // villes -> http://bulk.openweathermap.org/sample/

    private const CORNEBARRIEU = '3023606';
    private const BLAGNAC      = '3032469';

    private const API_URL    = 'http://api.openweathermap.org/';
    private const API_KEY    = 'b8818ae6ec5845bc74a39443b5b58748';
    private const API_LANG   = 'fr';
    private const API_METRIC = 'metric';

    private const REQUEST_TTL = 300;
    private const DEFAULT_TTL = 86400 * 2;

    private RedisAdapter         $cache;
    private array                $data;
    private string               $city;
    private int                  $nbDays;
    private string               $hash;

    public function __construct(string $city, int $nbDays)
    {
        $redis = new Redis();
        $redis->connect('redis');

        $this->city   = $city ?: self::CORNEBARRIEU;
        $this->nbDays = $nbDays;
        $this->cache  = new RedisAdapter($redis, 'Weather', self::DEFAULT_TTL);
        $this->data   = $this->request();
    }

    public function request()
    {
        $params = [
                'lang'  => self::API_LANG,
                'units' => self::API_METRIC,
                'appid' => self::API_KEY,
            ] + (ctype_digit($this->city) ? ['id' => $this->city] : ['q' => $this->city]);

        $url        = self::API_URL . 'data/2.5/forecast?' . http_build_query($params);
        $this->hash = substr(md5($url), 0, 8);

        $item = $this->cache->getItem("request.$this->hash");
        if (!$item->isHit()) {
            $data = json_decode(file_get_contents($url), true) ?: [];
            $item->set($data);
            $item->expiresAfter(self::REQUEST_TTL);
            $this->cache->save($item);

            $this->savePoints($data['list'] ?? []);
        }

        return $item->get();
    }

    private function allpointsGet($Ymd)
    {
        $item = $this->cache->getItem("allpoints.$Ymd.$this->hash");
        return [
            'item'       => $item,
            'timestamps' => $item->isHit() ? $item->get() : [],
        ];
    }

    private function allpointsSet($data)
    {
        $item       = $data['item'];
        $timestamps = array_unique($data['timestamps']);
        sort($timestamps);
        $item->set($timestamps);
        $this->cache->save($item);
    }

    private function savePoint($timestamp, $point)
    {
        $pointItem = $this->cache->getItem("point.$timestamp.$this->hash");
        $pointItem->set($point);
        $this->cache->save($pointItem);
    }

    private function savePoints($points)
    {
        $allpoints = [];
        foreach ($points as $point) {
            $dt  = (int)$point['dt'];
            $Ymd = date('Ymd', $dt);

            $allpoints[$Ymd]                 ??= $this->allpointsGet($Ymd);
            $allpoints[$Ymd]['timestamps'][] = $dt;

            $this->savePoint($dt, $point);
        }

        foreach ($allpoints as $Ymd => $data) {
            $this->allpointsSet($data);
        }
    }

    private function points()
    {
        $points = [];

        $interval = new DateInterval('P1D');
        $date     = new DateTime();
        $date->setTime(12, 0, 0);

        for ($i = 1; $i <= $this->nbDays; $i++) {
            $timestamps = $this->allpointsGet($date->format('Ymd'))['timestamps'];
            foreach ($timestamps as $dt) {
                $pointItem = $this->cache->getItem("point.$dt.$this->hash");
                if ($pointItem->isHit()) {
                    $points[$dt] = $pointItem->get();
                }
            }

            $date = $date->add($interval);
        }

        ksort($points);
        return array_values($points);
    }

    public function series()
    {
        foreach ($this->points() as $item) {
            $series['date'][]       = $item['dt'];                                // timestamp
            $series['temp'][]       = $item['main']['temp'] ?? 0;                 // °c
            $series['feels_like'][] = $item['main']['feels_like'] ?? 0;           // °c
            $series['temp_min'][]   = $item['main']['temp_min'] ?? 0;             // °c
            $series['temp_max'][]   = $item['main']['temp_max'] ?? 0;             // °c
            $series['pressure'][]   = $item['main']['pressure'] ?? 0;             // hPa
            $series['humidity'][]   = $item['main']['humidity'] ?? 0;             // %
            $series['wind_speed'][] = ($item['wind']['speed'] ?? 0) * 3.6;        // m/s converti en km/h
            $series['mm'][]         = $item['rain']['3h'] ?? 0;                   // mm : precipitation sur 3h
            $series['clouds'][]     = $item['clouds']['all'] ?? 0;                // %
            $series['icon'][]       = isset($item['weather'][0]['icon']) ? 'http://openweathermap.org/img/wn/' . $item['weather'][0]['icon'] . '@2x.png' : '';
            $series['desc'][]       = $item['weather'][0]['description'] ?? '';
        }

        foreach ($series as $serie => $values) {
            $series[$serie] = \array_slice($series[$serie], 0, 8 * $this->nbDays);
        }

        return $series;
    }

    public function data()
    {
        return $this->data;
    }
}
