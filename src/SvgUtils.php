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

class SvgUtils
{
    public static function main(float $height, float $width, string $class, $content)
    {
        if (\is_callable($content)) {
            $content = \call_user_func($content, '');
        }

        return '<svg '
            . ' class="' . $class . '"'
            . ' xmlns="http://www.w3.org/2000/svg"'
            . ' height="' . $height . '"'
            . ' width="' . $width . '"'
            . ' viewBox="0 0 ' . $width . ' ' . $height . '"'
            . ">$content</svg>";
    }

    public static function image(string $href, float $x, float $y, float $width, float $height)
    {
        return '<image '
            . ' xlink:href="' . $href . '"'
            . ' x="' . round($x, 3) . '"'
            . ' y="' . round($y, 3) . '"'
            . ' height="' . round($height, 3) . '"'
            . ' width="' . round($width, 3) . '"'
            . '/>';
    }

    public static function circle(string $color, float $radius, float $x, float $y, float $opacity = 1)
    {
        return '<circle '
            . ($opacity !== 1 ? 'fill-opacity="' . $opacity . '" ' : '')
            . ' fill="' . $color . '"'
            . ' cx="' . round($x, 3) . '"'
            . ' cy="' . round($y, 3) . '"'
            . ' r="' . round($radius, 3) . '"'
            . '></circle>';
    }

    public static function text(string $color, string $text, float $x, float $y, float $size = 12, float $opacity = 1, string $font = 'monospace')
    {
        return '<text '
            . ($opacity !== 1 ? 'fill-opacity="' . $opacity . '" ' : '')
            . ' fill="' . $color . '"'
            . ' x="' . round($x, 3) . '"'
            . ' y="' . round($y, 3) . '"'
            . ' font-family="' . $font . '"'
            . ' font-size="' . $size . '"'
            . '>' . $text . '</text>';
    }

    public static function line(string $color, float $x1, float $y1, float $x2, float $y2, float $width = 1, float $opacity = 1, string $dasharray = '')
    {
        return '<line '
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

    public static function rect(string $color, float $x, float $y, float $width, float $height, float $opacity = 1)
    {
        return '<rect '
            . ($opacity !== 1 ? 'fill-opacity="' . $opacity . '" ' : '')
            . ' fill="' . $color . '"'
            . ' x="' . round($x, 3) . '"'
            . ' y="' . round($y, 3) . '"'
            . ' width="' . round($width, 3) . '"'
            . ' height="' . round($height, 3) . '"'
            . '></rect>';
    }
}
