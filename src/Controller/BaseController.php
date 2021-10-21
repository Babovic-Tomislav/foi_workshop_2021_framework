<?php

namespace App\Controller;

use App\Twig\RenderFunction;
use DirectoryIterator;
use Twig\Environment;

class BaseController
{
    /** @var Environment  */
    protected $twig;

    public function __construct()
    {
        $loader = new \Twig\Loader\FilesystemLoader('../src/templates');
        $twig = new \Twig\Environment($loader);

        $this->twig = $twig;
    }

    protected function render(string $layout, array $data = []) {
        echo $this->twig->render($layout, $data);
    }
}

