<?php

use Arnapou\SimpleSite\Core\Controller;
use Arnapou\SimpleSite\Utils;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

use Symfony\Component\HttpFoundation\JsonResponse;

use function count;

class ApiClient
{

    // Temps courant
    // doc    -> https://openweathermap.org/current
    // ex     -> https://api.openweathermap.org/data/2.5/weather?id=3023606&lang=fr&units=metric&appid=b8818ae6ec5845bc74a39443b5b58748

    // Temps Toutes les 3h sur 5 jours
    // doc    -> https://openweathermap.org/forecast5
    // ex     -> https://api.openweathermap.org/data/2.5/forecast?id=3023606&lang=fr&units=metric&appid=b8818ae6ec5845bc74a39443b5b58748

    // icons  -> https://openweathermap.org/weather-conditions
    // villes -> http://bulk.openweathermap.org/sample/

    const CORNEBARRIEU = 3023606;
    const BLAGNAC      = 3032469;

    const API_URL    = 'http://api.openweathermap.org/';
    const API_KEY    = 'b8818ae6ec5845bc74a39443b5b58748';
    const API_LANG   = 'fr';
    const API_METRIC = 'metric';

    const SVG_BAR        = 30;
    const SVG_FONT       = 20;
    const SVG_FONTFACTOR = .6;
    const SVG_MARGIN     = self::SVG_FONT * 4 * self::SVG_FONTFACTOR + 5;

    const THRESHOLD_COLOR = '#000088';
    const THRESHOLD_ALPHA = 0.1;
    const GRID_COLOR      = '#000000';
    const GRID_ALPHA      = 0.2;
    const TEXT_COLOR      = '#000000';
    const TEXT_ALPHA      = 0.5;

    const TTL = 900;

    public  $url;
    public  $data;
    public  $series = [];
    public  $tsMin;
    public  $tsMax;
    private $nb;
    private $width;

    public function __construct(string $city, int $nbDays, ?CacheItemPoolInterface $cache = null)
    {
        $this->initData($city, $cache);
        $this->initSeries($nbDays);

        $this->nb    = count($this->series['date'] ?? []);
        $this->width = $this->nb * self::SVG_BAR + self::SVG_MARGIN;
        if (!$this->isEmpty()) {
            $this->tsMin = 3600 * floor($this->series['date'][0] / 3600);
            $this->tsMax = 3600 * ceil($this->series['date'][$this->nb - 1] / 3600);
        }
    }

    public function isEmpty(): bool
    {
        return $this->nb ? false : true;
    }

    private function initData(string $city, ?CacheItemPoolInterface $cache = null)
    {
        $params = [
            'lang'  => self::API_LANG,
            'units' => self::API_METRIC,
            'appid' => self::API_KEY,
        ];
        if (ctype_digit($city)) {
            $params['id'] = $city;
        } else {
            $params['q'] = $city;
        }
        $this->url = self::API_URL . 'data/2.5/forecast?' . http_build_query($params);

        $getData = function () {
            return json_decode(file_get_contents($this->url), true) ?: [];
        };

        if ($cache) {
            $item = $cache->getItem(md5($this->url));
            if (!$item->isHit()) {
                $item->set($getData());
                $item->expiresAfter(self::TTL);
                $cache->save($item);
            }
            $this->data = $item->get();
        } else {
            $this->data = $getData();
        }
    }

    private function initSeries(int $nbDays)
    {
        foreach (($this->data['list'] ?? []) as $item) {
            $this->series['date'][]       = $item['dt'];                                // timestamp
            $this->series['temp'][]       = $item['main']['temp'] ?? 0;                 // °c
            $this->series['feels_like'][] = $item['main']['feels_like'] ?? 0;           // °c
            $this->series['temp_min'][]   = $item['main']['temp_min'] ?? 0;             // °c
            $this->series['temp_max'][]   = $item['main']['temp_max'] ?? 0;             // °c
            $this->series['pressure'][]   = $item['main']['pressure'] ?? 0;             // hPa
            $this->series['humidity'][]   = $item['main']['humidity'] ?? 0;             // %
            $this->series['wind_speed'][] = ($item['wind']['speed'] ?? 0) * 3.6;        // m/s converti en km/h
            $this->series['mm'][]         = $item['rain']['3h'] ?? 0;                   // mm : precipitation sur 3h
            $this->series['clouds'][]     = $item['clouds']['all'] ?? 0;                // %
            $this->series['icon'][]       = isset($item['weather'][0]['icon']) ? 'http://openweathermap.org/img/wn/' . $item['weather'][0]['icon'] . '@2x.png' : '';
            $this->series['desc'][]       = $item['weather'][0]['description'] ?? '';
        }

        foreach ($this->series as $serie => $values) {
            $this->series[$serie] = array_slice($this->series[$serie], 0, 8 * $nbDays);
        }
    }

    public function svglines(string $serie, string $color, float $vGrid = 1, float $threshold = 0)
    {
        if (empty($values = $this->series[$serie] ?? [])) {
            return '';
        }

        $min = floor(min($values));
        $max = ceil(max($values));
        if ($max - $min == 0) {
            $min -= $vGrid;
            $max += $vGrid;
        }
        $height = ($max - $min + 2 * $vGrid) * self::SVG_FONT * 1.25 / $vGrid;

        $calcY = function ($value) use ($height, $min, $max, $vGrid) {
            return $height - ($value - $min + $vGrid) * $height / ($max - $min + 2 * $vGrid);
        };

        $svg = '<svg class="' . $serie . '" xmlns="http://www.w3.org/2000/svg" height="' . $height . '" width="' . $this->width . '" viewBox="0 0 ' . $this->width . ' ' . $height . '">';
        $this->svgDays($svg, $height);
        // grid + text
        for ($i = $min - $vGrid; $i < $max + $vGrid; $i++) {
            if ($i % $vGrid == 0) {
                $y = $calcY($i);
                Svg::text($svg, self::TEXT_COLOR, $i, 2 + self::SVG_FONT * self::SVG_FONTFACTOR * (4 - strlen($i)), $y + 5, self::SVG_FONT, self::TEXT_ALPHA);
                Svg::line($svg, self::GRID_COLOR, self::SVG_MARGIN, $y, $this->width, $y, 1, self::GRID_ALPHA);
            }
        }
        // zone grise
        if ($threshold < 0) {
            $y = $calcY(-$threshold);
            Svg::rect($svg, self::THRESHOLD_COLOR, self::SVG_MARGIN, 0, $this->nb * self::SVG_BAR, $y, self::THRESHOLD_ALPHA);
        } else {
            $y = $calcY($threshold);
            Svg::rect($svg, self::THRESHOLD_COLOR, self::SVG_MARGIN, $y, $this->nb * self::SVG_BAR, $height - $y, self::THRESHOLD_ALPHA);
        }
        // points
        $x1 = $y1 = 0;
        for ($i = 0; $i < $this->nb; $i++) {
            $y = $calcY($values[$i]);
            $x = $this->svgX($i);
            Svg::circle($svg, $color, 0.35 * self::SVG_FONT, $x, $y);
            if ($i) {
                Svg::line($svg, $color, $x1, $y1, $x, $y, 0.15 * self::SVG_FONT);
            }
            [$x1, $y1] = [$x, $y];
        }

        return "$svg</svg>";
    }

    public function svgimg(string $serie)
    {
        if (empty($values = $this->series[$serie] ?? [])) {
            return '';
        }

        $scale  = 1.4;
        $height = self::SVG_BAR * $scale;

        $svg = '<svg class="' . $serie . '" xmlns="http://www.w3.org/2000/svg" height="' . $height . '" width="' . $this->width . '" viewBox="0 0 ' . $this->width . ' ' . $height . '">';
        // $this->svgDays($svg, $height);
        for ($i = 0; $i < $this->nb; $i++) {
            $x = $this->svgX($i) - (self::SVG_BAR * $scale) / 2;
            Svg::image($svg, $values[$i], $x, 0, self::SVG_BAR * $scale, self::SVG_BAR * $scale);
        }
        return "$svg</svg>";
    }

    private function svgX(int $indexOrTimestamp)
    {
        $ts = $indexOrTimestamp > 10000 ? $indexOrTimestamp : $this->series['date'][$indexOrTimestamp];
        $w  = $this->width - self::SVG_MARGIN - self::SVG_BAR;
        $x  = ($ts - $this->tsMin) * $w / ($this->tsMax - $this->tsMin);
        return self::SVG_MARGIN + self::SVG_BAR * .5 + $x;
    }

    private function svgDays(string &$svg, int $height): void
    {
        $ts      = $this->tsMin;
        $numJour = 0;
        while ($ts <= $this->tsMax) {
            $x = $this->svgX($ts);
            switch (intval(date('H', $ts))) {
                case 0:
                    $numJour++;
                    if ($numJour % 2) {
                        Svg::rect($svg, self::GRID_COLOR, $x, 0, $this->svgX($ts + 24 * 3600) - $x, $height, self::GRID_ALPHA * .5);
                    }
                    break;
                case 7:
                case 17:
                    Svg::line($svg, self::THRESHOLD_COLOR, $x, 0, $x, $height, 1, self::GRID_ALPHA * 2, .2 * self::SVG_FONT);
                    break;
                // case 12:
                //     Svg::line($svg, self::SVG_GRID_COLOR, $x, 0, $x, $height, 1, self::SVG_GRID_OPACITY, .5 * self::SVG_FONT);
                //     break;
            }
            $ts += 3600;
        }
    }

}

class Svg
{
    public static function image(string &$svg, string $href, float $x, float $y, float $width, float $height)
    {
        $svg .= '<image '
            . ' xlink:href="' . $href . '"'
            . ' x="' . round($x, 3) . '"'
            . ' y="' . round($y, 3) . '"'
            . ' height="' . round($height, 3) . '"'
            . ' width="' . round($width, 3) . '"'
            . '/>';
    }

    public static function circle(string &$svg, string $color, float $radius, float $x, float $y, float $opacity = 1)
    {
        $svg .= '<circle '
            . ($opacity !== 1 ? 'fill-opacity="' . $opacity . '" ' : '')
            . ' fill="' . $color . '"'
            . ' cx="' . round($x, 3) . '"'
            . ' cy="' . round($y, 3) . '"'
            . ' r="' . round($radius, 3) . '"'
            . '></circle>';
    }

    public static function text(string &$svg, string $color, string $text, float $x, float $y, float $size = 12, float $opacity = 1, string $font = 'monospace')
    {
        $svg .= '<text '
            . ($opacity !== 1 ? 'fill-opacity="' . $opacity . '" ' : '')
            . ' fill="' . $color . '"'
            . ' x="' . round($x, 3) . '"'
            . ' y="' . round($y, 3) . '"'
            . ' font-family="' . $font . '"'
            . ' font-size="' . $size . '"'
            . '>' . $text . '</text>';
    }

    public static function line(string &$svg, string $color, float $x1, float $y1, float $x2, float $y2, float $width = 1, float $opacity = 1, string $dasharray = '')
    {
        $svg .= '<line '
            . ($opacity !== 1 ? 'stroke-opacity="' . $opacity . '" ' : '')
            . ($dasharray ? 'stroke-dasharray="' . $dasharray . '" ' : '')
            . ' stroke="' . $color . '"'
            . ' stroke-width="' . $width . '"'
            . ' x1="' . round($x1, 3) . '"'
            . ' y1="' . round($y1, 3) . '"'
            . ' x2="' . round($x2, 3) . '"'
            . ' y2="' . round($y2, 3) . '"'
            . '></line>';
    }

    public static function rect(string &$svg, string $color, float $x, float $y, float $width, float $height, float $opacity = 1)
    {
        $svg .= '<rect '
            . ($opacity !== 1 ? 'fill-opacity="' . $opacity . '" ' : '')
            . ' fill="' . $color . '"'
            . ' x="' . round($x, 3) . '"'
            . ' y="' . round($y, 3) . '"'
            . ' width="' . round($width, 3) . '"'
            . ' height="' . round($height, 3) . '"'
            . '></rect>';
    }
}

return new class() extends Controller {

    public function configure(): void
    {
        $this->addRoute('weather{days}{city}{ext}', [$this, 'weather'], 'weather')
            ->setRequirement('days', '[2345]?')
            ->setRequirement('city', '(-[a-zA-Z ]+)?')
            ->setRequirement('ext', '(\.json)?');
    }

    public function weather(?string $days = null, ?string $city = null, ?string $ext = null)
    {
        Utils::mkdir($directory = $this->container()->Config()->path_cache() . '/weather');
        $cache = new FilesystemAdapter('', ApiClient::TTL, $directory);

        $client = new ApiClient(
            trim($city ?: ApiClient::CORNEBARRIEU, '-'),
            intval($days ?: 5),
            $cache
        );

        if ($ext) {
            return new JsonResponse($client->data);
        } else {
            return $this->render('@templates/weather.twig', ['weather' => $client]);
        }
    }
};
