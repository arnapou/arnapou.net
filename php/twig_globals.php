<?php

use Arnapou\SimpleSite\Core\PhpCode;
use Arnapou\SimpleSite\Core\ServiceContainer;

return new class() implements PhpCode {

    public function init(ServiceContainer $container): void
    {
        $twig = $container->TwigEnvironment();

        $parameters = $container->Database()->getTable('parameters');
        foreach ($parameters as $key => $data) {
            $twig->addGlobal($key, $data['value'] ?? '');
        }
        $twig->addGlobal('age', $this->age($parameters->get('birthday')['value']));
    }

    protected function age($birthday)
    {
        $arr1 = explode('/', $birthday);
        $arr2 = explode('/', date('d/m/Y'));
        if (($arr1[1] < $arr2[1]) || (($arr1[1] == $arr2[1]) && ($arr1[0] <= $arr2[0]))) {
            return $arr2[2] - $arr1[2];
        }
        return $arr2[2] - $arr1[2] - 1;
    }
};
