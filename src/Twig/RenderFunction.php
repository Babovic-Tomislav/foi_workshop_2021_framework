<?php

namespace App\Twig;

use App\Service\Request\Request;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RenderFunction extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('render', [$this, 'render']),
        ];
    }

    public function render(string $method) {
        list($controller, $action) = explode('::', $method);

        return (new $controller)->$action(new Request());
    }
}