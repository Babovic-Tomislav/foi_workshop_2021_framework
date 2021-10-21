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

        $files = new DirectoryIterator(__DIR__ . '/../Twig');

        foreach($files as $file) {
            if ($file->isDot()) {
                continue;
            }

            $class = 'App\\Twig\\' . rtrim($file->getFilename(), '.php');

            $twig->addExtension(new $class);
        }
        
        if (file_exists('../src/Twig')) {
            $files = new DirectoryIterator( '../src/Twig');

            foreach($files as $file) {
                if ($file->isDot()) {
                    continue;
                }

                $class = 'App\\Twig\\' . rtrim($file->getFilename(), '.php');

                $twig->addExtension(new $class);
            }
        }
            
            

        $this->twig = $twig;
    }

    protected function render(string $layout, array $data = []) {
        echo $this->twig->render($layout, $data);
    }
}

