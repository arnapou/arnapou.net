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
    private const DEFAULT_TTL = 86400 + 3600;

    private RedisAdapter         $cache;
    private array                $data;
    private string               $city;
    private int                  $nbDays;
    private string               $key;

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

        $url       = self::API_URL . 'data/2.5/forecast?' . http_build_query($params);
        $this->key = date('Ymd') . '.' . substr(md5($url), 0, 8);

        $item = $this->cache->getItem("request.$this->key");
        if (!$item->isHit()) {
            $data = json_decode(file_get_contents($url), true) ?: [];
            $item->set($data);
            $item->expiresAfter(self::REQUEST_TTL);
            $this->cache->save($item);

            $this->savePoints($data['list'] ?? []);
        }

        return $item->get();
    }

    private function savePoints($points)
    {
        $allItem = $this->cache->getItem("allpoints.$this->key");

        $timestamps = $allItem->isHit() ? $allItem->get() : [];
        foreach ($points as $point) {
            $timestamps[] = $dt = (int)$point['dt'];

            $pointItem = $this->cache->getItem("point.$dt.$this->key");
            $pointItem->set($point);
            $this->cache->save($pointItem);
        }

        $timestamps = array_unique($timestamps);
        sort($timestamps);
        $allItem->set($timestamps);
        $this->cache->save($allItem);
    }

    private function points()
    {
        $points = [];
        foreach (($this->data['list'] ?? []) as $point) {
            $points[(int)$point['dt']] = $point;
        }

        $allItem    = $this->cache->getItem("allpoints.$this->key");
        $timestamps = $allItem->isHit() ? $allItem->get() : [];
        foreach ($timestamps as $dt) {
            if (isset($points[$dt])) {
                continue;
            }

            $pointItem = $this->cache->getItem("point.$dt.$this->key");
            if ($pointItem->isHit()) {
                $points[$dt] = $pointItem->get();
            }
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
