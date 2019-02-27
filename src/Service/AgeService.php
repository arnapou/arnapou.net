<?php

/*
 * This file is part of the arnapou.net site package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

class AgeService
{
    /**
     * @var string
     */
    private $birthday;
    /**
     * @var int
     */
    private $age;

    /**
     * Age constructor.
     * @param string $birthday
     */
    public function __construct($birthday)
    {
        $this->birthday = $birthday;
        $this->age      = $this->calculate($this->birthday);
    }

    /**
     * @return string
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * @return int
     */
    public function getValue()
    {
        return $this->age;
    }

    /**
     * @param string $birthday
     * @return int
     */
    private function calculate($birthday)
    {
        $arr1 = explode('/', $birthday);
        $arr2 = explode('/', date('d/m/Y'));

        if (($arr1[1] < $arr2[1]) || (($arr1[1] == $arr2[1]) && ($arr1[0] <= $arr2[0]))) {
            return $arr2[2] - $arr1[2];
        }

        return $arr2[2] - $arr1[2] - 1;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->age;
    }
}
