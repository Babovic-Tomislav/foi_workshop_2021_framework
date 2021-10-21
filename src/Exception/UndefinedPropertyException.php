<?php

namespace App\Exception;

use Exception;

class UndefinedPropertyException extends Exception
{
    public function __construct(string $property)
    {
        parent::__construct("Undefined property $property", 900);
    }
}