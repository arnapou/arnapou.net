<?php

use Arnapou\SimpleSite\Core\Controller;
use Arnapou\SimpleSite\Utils;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

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

    const API_URL = 'http://api.openweathermap.org/';
    const API_KEY = 'b8818ae6ec5845bc74a39443b5b58748';

    const SVG_BAR        = 30;
    const SVG_FONT       = 20;
    const SVG_FONTFACTOR = .6;

    const TTL = 900;

    public $data;
    public $series = [];
    public $icon;
    public $url;

    public function __construct(string $city, int $nbDays, ?CacheItemPoolInterface $cache = null)
    {
        $params = [
            'lang'  => 'fr',
            'units' => 'metric',
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
            $item = $cache->getItem(md5($url));
            if (!$item->isHit()) {
                $item->set($getData());
                $item->expiresAfter(self::TTL);
                $cache->save($item);
            }
            $this->data = $item->get();
        } else {
            $this->data = $getData();
        }

        $this->normalize($nbDays);
    }

    private function normalize(int $nbDays)
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
        $this->icon = $this->series['icon'][0] ?? '';

        foreach ($this->series as $serie => $values) {
            $this->series[$serie] = array_slice($this->series[$serie], 0, 8 * $nbDays);
        }
    }

    public function svglines(string $serie, string $color, float $vGrid = 1, float $threshold = 0)
    {
        if (empty($values = $this->series[$serie] ?? [])) {
            return '';
        }

        $margin = self::SVG_FONT * 4 * self::SVG_FONTFACTOR + 5;
        $min    = floor(min($values));
        $max    = ceil(max($values));
        $nb     = count($values);
        $width  = $nb * self::SVG_BAR + $margin;
        if ($max - $min == 0) {
            $min -= $vGrid;
            $max += $vGrid;
        }
        $height = ($max - $min + 2 * $vGrid) * self::SVG_FONT * 1.25 / $vGrid;

        $calcY = function ($value) use ($height, $min, $max, $vGrid) {
            return $height - ($value - $min + $vGrid) * $height / ($max - $min + 2 * $vGrid);
        };
        $calcX = function ($i) use ($margin) {
            return $margin + self::SVG_BAR * ($i + .5);
        };

        $svg = '<svg class="' . $serie . '" xmlns="http://www.w3.org/2000/svg" height="' . $height . '" width="' . $width . '" viewBox="0 0 ' . $width . ' ' . $height . '">';
        // grid + text
        for ($i = $min - $vGrid; $i < $max + $vGrid; $i++) {
            if ($i % $vGrid == 0) {
                $y = $calcY($i);
                Svg::text($svg, '#666666', $i, 2 + self::SVG_FONT * self::SVG_FONTFACTOR * (4 - strlen($i)), $y + 5, self::SVG_FONT);
                Svg::rect($svg, '#cccccc', $margin, $y, $nb * self::SVG_BAR, 1);
            }
        }
        if ($threshold < 0) {
            $y = $calcY(-$threshold);
            Svg::rect($svg, '#cccccc', $margin, 0, $nb * self::SVG_BAR, $y, .25);
        } else {
            $y = $calcY($threshold);
            Svg::rect($svg, '#cccccc', $margin, $y, $nb * self::SVG_BAR, $height - $y, .25);
        }
        // days
        for ($i = 0; $i < \count($values); $i++) {
            $ts = $this->series['date'][$i];
            $x  = $calcX($i);
            if ($ts % 43200 == 0) {
                Svg::line($svg, '#cccccc', $x, 0, $x, $height, 1, 1, ($ts % 86400) ? '' : round(0.15 * self::SVG_FONT));
            }
        }
        // points
        $x1 = $y1 = 0;
        for ($i = 0; $i < \count($values); $i++) {
            $y = $calcY($values[$i]);
            $x = $calcX($i);
            Svg::circle($svg, $color, 0.35 * self::SVG_FONT, $x, $y);
            if ($i) {
                Svg::line($svg, $color, $x1, $y1, $x, $y, 0.15 * self::SVG_FONT);
            }
            [$x1, $y1] = [$x, $y];
        }

        $svg .= '</svg>';
        return $svg;
    }

    public function svgimg(string $serie)
    {
        if (empty($values = $this->series[$serie] ?? [])) {
            return '';
        }

        $scale  = 1.5;
        $margin = self::SVG_FONT * 4 * self::SVG_FONTFACTOR + 5;
        $nb     = count($values);
        $width  = $nb * self::SVG_BAR + $margin;
        $height = self::SVG_BAR * $scale;

        $calcX = function ($i) use ($margin) {
            return $margin + self::SVG_BAR * ($i + .5);
        };

        $svg = '<svg class="' . $serie . '" xmlns="http://www.w3.org/2000/svg" height="' . $height . '" width="' . $width . '" viewBox="0 0 ' . $width . ' ' . $height . '">';
        // days
        for ($i = 0; $i < \count($values); $i++) {
            $ts = $this->series['date'][$i];
            if ($ts % 86400 == 0) {
                $x = $calcX($i);
                Svg::rect($svg, '#cccccc', $x, 0, 1, $height);
            }
        }
        // images
        for ($i = 0; $i < \count($values); $i++) {
            $x = $calcX($i) - (self::SVG_BAR * $scale) / 2;
            Svg::image($svg, $values[$i], $x, 0, self::SVG_BAR * $scale, self::SVG_BAR * $scale);
        }
        $svg .= '</svg>';
        return $svg;
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

    public static function text(string &$svg, string $color, string $text, float $x, float $y, float $size = 12, string $font = 'monospace')
    {
        $svg .= '<text '
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
        $this->addRoute('weather{days}', [$this, 'weather'], 'weather')->setRequirement('days', '[2345]?');
        $this->addRoute('weather{days}-{city}', [$this, 'weather'], 'weather')->setRequirement('days', '[2345]?');
    }

    public function weather(?string $days = null, ?string $city = null)
    {
        Utils::mkdir($directory = $this->container()->Config()->path_cache() . '/weather');
        $cache = new FilesystemAdapter('', ApiClient::TTL, $directory);

        $client = new ApiClient(
            $city ?: ApiClient::CORNEBARRIEU,
            intval($days ?: 5),
            $cache
        );
        return $this->render('@templates/weather.twig', ['weather' => $client]);
    }
};
