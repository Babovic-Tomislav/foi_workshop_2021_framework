<?php

use App\Service\Request\Request;
use App\Service\Router\Router;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();


require_once '../src/Resources/config/routes.php';
$router = new Router();

$router->resolve(new Request());