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

class WeatherGraphics
{
    const SVG_FONT       = 20;
    const SVG_FONTFACTOR = .6;
    const SVG_BAR        = 30;
    const SVG_LINE       = 1.25 * self::SVG_FONT;
    const SVG_MARGIN     = self::SVG_FONT * 4 * self::SVG_FONTFACTOR + 5;

    const DOT_RADIUS = 0.35 * self::SVG_FONT;
    const LINE_WIDTH = 0.15 * self::SVG_FONT;

    const THRESHOLD_COLOR = '#000088';
    const THRESHOLD_ALPHA = 0.1;
    const GRID_COLOR      = '#000000';
    const GRID_ALPHA      = 0.2;
    const WEEKEND_COLOR   = '#000000';
    const WEEKEND_ALPHA   = .9;
    const WEEKEND_WIDTH   = 2;
    const TEXT_COLOR      = '#000000';
    const TEXT_ALPHA      = 0.5;

    public $series = [];
    public $tsMin;
    public $tsMax;
    private $nb;
    private $width;

    public function __construct(WeatherApiClient $apiClient)
    {
        $this->series = $apiClient->series();

        $this->nb    = \count($this->series['date'] ?? []);
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

    public function svglines($series, $colors, float $vGrid = 1, float $threshold = 0)
    {
        if (!$this->svglinesInit($series, $colors, $vGrid, $allValues, $min, $max)) {
            return '';
        }

        $height     = ($max - $min + 2 * $vGrid) * self::SVG_LINE / $vGrid;
        $realHeight = $height + self::WEEKEND_WIDTH;

        $calcY = function ($value) use ($height, $min, $max, $vGrid) {
            return $height - ($value - $min + $vGrid) * $height / ($max - $min + 2 * $vGrid);
        };

        return SvgUtils::main(
            $realHeight,
            $this->width,
            $series[0],
            function ($svg) use ($colors, $allValues, $threshold, $height, $calcY, $max, $min, $vGrid) {
                $svg .= SvgUtils::rect('#ffffff', 0, 0, $this->width, $height);

                $this->svgTimes($svg, (int)$height);
                $this->svgGridText($svg, $vGrid, $min, $max, $calcY, $height);
                $this->svgThreshold($svg, $threshold, $height, $calcY);

                // points
                foreach ($allValues as $key => $values) {
                    $x1 = $y1 = 0;
                    for ($i = 0; $i < $this->nb; $i++) {
                        $x   = $this->svgX($i);
                        $y   = $calcY($values[$i]);
                        $svg .= SvgUtils::circle($colors[$key], self::DOT_RADIUS, $x, $y);
                        if ($i) {
                            $svg .= SvgUtils::line($colors[$key], $x1, $y1, $x, $y, self::LINE_WIDTH);
                        }
                        [$x1, $y1] = [$x, $y];
                    }
                }
                return $svg;
            }
        );
    }

    private function svglinesInit(&$series, &$colors, $vGrid, &$allValues, &$min, &$max): bool
    {
        $series = (array)$series;
        $colors = (array)$colors;

        $allValues = [];
        foreach ($series as $key => $serie) {
            if (empty($values = $this->series[$serie] ?? [])) {
                unset($series[$key], $colors[$key]);
            } else {
                $allValues[$key] = $values;
            }
        }
        if (\count($series) !== \count($colors) || empty($series)) {
            return false;
        }

        $max = $min = null;
        foreach ($allValues as $values) {
            $min = $min === null ? min($values) : min($min, ...$values);
            $max = $max === null ? max($values) : max($max, ...$values);
        }
        [$min, $max] = [floor($min), ceil($max)];
        if ($max - $min == 0) {
            $min -= $vGrid;
            $max += $vGrid;
        }
        return true;
    }

    public function svgimg(string $serie)
    {
        if (empty($values = $this->series[$serie] ?? [])) {
            return '';
        }

        $scale  = 1.4;
        $height = self::SVG_BAR * $scale;

        return SvgUtils::main(
            $height,
            $this->width,
            $serie,
            function ($svg) use ($scale, $values) {
                for ($i = 0; $i < $this->nb; $i++) {
                    $x   = $this->svgX($i) - (self::SVG_BAR * $scale) / 2;
                    $svg .= SvgUtils::image($values[$i], $x, 0, self::SVG_BAR * $scale, self::SVG_BAR * $scale);
                }
                return $svg;
            }
        );
    }

    private function svgX(int $indexOrTimestamp)
    {
        $ts = $indexOrTimestamp > 10000 ? $indexOrTimestamp : $this->series['date'][$indexOrTimestamp];
        $w  = $this->width - self::SVG_MARGIN - self::SVG_BAR;
        $x  = ($ts - $this->tsMin) * $w / ($this->tsMax - $this->tsMin);
        return self::SVG_MARGIN + self::SVG_BAR * .5 + $x;
    }

    private function svgTimes(string &$svg, int $height): void
    {
        $ts = (int)$this->tsMin - 86400;       // on demarre avant pour etre sur de chopper le premier creneau
        while ($ts <= $this->tsMax) {
            $x = $this->svgX($ts);
            switch ($hour = (int)date('H', $ts)) {
                case 0:
                    $isWeekend = \in_array(date('N', $ts + 14400), [6, 7]);
                    $x1        = max($x, self::SVG_MARGIN);
                    $x2        = $this->svgX($ts + 86400);
                    if ($x2 < self::SVG_MARGIN) {
                        break;
                    }
                    if (floor($ts / 86400) % 2) {
                        $svg .= SvgUtils::rect(self::GRID_COLOR, $x1, 0, $x2 - $x1, $height, self::GRID_ALPHA * .5);
                    }
                    if ($isWeekend) {
                        $svg .= SvgUtils::rect(self::WEEKEND_COLOR, $x1, $height, $x2 - $x1, self::WEEKEND_WIDTH, self::WEEKEND_ALPHA);
                    }
                    break;
                case 7:
                case 17:
                    if ($x >= self::SVG_MARGIN) {
                        $svg .= SvgUtils::line(self::THRESHOLD_COLOR, $x, 0, $x, $height, 1, self::GRID_ALPHA * 2, (string)(.2 * self::SVG_FONT));
                    }
                    break;
            }
            $ts += 3600;
        }
    }

    private function svgGridText(&$svg, float $vGrid, float $min, float $max, Closure $calcY, float $height)
    {
        for ($i = $min - $vGrid; $i < $max + $vGrid; $i++) {
            if ($i % $vGrid == 0) {
                $y     = $calcY($i);
                $xText = 2 + self::SVG_FONT * self::SVG_FONTFACTOR * (4 - \strlen((string)$i));
                $yText = $y + 0.3 * self::SVG_FONT;
                if ($yText < $height && $yText - 0.9 * self::SVG_FONT >= 0) {
                    $svg .= SvgUtils::text(self::TEXT_COLOR, "$i", $xText, $yText, self::SVG_FONT, self::TEXT_ALPHA);
                }
                $svg .= SvgUtils::line(self::GRID_COLOR, self::SVG_MARGIN, $y, $this->width, $y, 1, self::GRID_ALPHA);
            }
        }
    }

    private function svgThreshold(&$svg, float $threshold, $height, Closure $calcY)
    {
        if ($threshold < 0) {
            $y   = $calcY(-$threshold);
            $svg .= SvgUtils::rect(self::THRESHOLD_COLOR, self::SVG_MARGIN, 0, $this->nb * self::SVG_BAR, $y, self::THRESHOLD_ALPHA);
        } else {
            $y   = $calcY($threshold);
            $svg .= SvgUtils::rect(self::THRESHOLD_COLOR, self::SVG_MARGIN, $y, $this->nb * self::SVG_BAR, $height - $y, self::THRESHOLD_ALPHA);
        }
    }
}
