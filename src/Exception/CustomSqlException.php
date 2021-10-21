<?php

namespace App\Exception;

use Exception;

class CustomSqlException extends Exception
{
    public function __construct(array $missingFields)
    {
        $message = "You need to fill: '";
        foreach ($missingFields as $field) {
            $message .= "$field, ";
        }

        $message = rtrim($message, ', ');

        $message .= "' fields";

        parent::__construct($message, 800);
    }
}

