<?php

namespace App\Helper;

class Arr
{
    public static function get(array $data, string $field)
    {
        $output = [];

        foreach ($data as $d) {
            $output []= $d[$field];
        }

        return $output;
    }
}

