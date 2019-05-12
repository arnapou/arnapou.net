<?php

namespace App;

use Cms\Site;

Site::instance()->EventDispatcher()->addListener(
    Site::onRun,
    function () {
        $twig = Site::instance()->TwigEnvironment();

        $parameters = Site::instance()->Database()->getTable('parameters');
        foreach ($parameters as $key => $data) {
            $twig->addGlobal($key, $data['value'] ?? '');
        }
        $twig->addGlobal('age', age($parameters->get('birthday')['value']));
    }
);

function age($birthday)
{
    $arr1 = explode('/', $birthday);
    $arr2 = explode('/', date('d/m/Y'));
    if (($arr1[1] < $arr2[1]) || (($arr1[1] == $arr2[1]) && ($arr1[0] <= $arr2[0]))) {
        return $arr2[2] - $arr1[2];
    }
    return $arr2[2] - $arr1[2] - 1;
}
