<?php

namespace App\Service\Database;

class Connection
{
    /** @var \PDO */
    private $conn;

    /** @var Connection */
    private static $instance;

    private function __construct()
    {
        $dbName = $_ENV['DB_NAME'];
        $dbUser = $_ENV['DB_USER'];
        $dbPassword = $_ENV['DB_PASSWORD'];

        $this->conn = new \PDO("mysql:host=localhost;dbname=$dbName", $dbUser, $dbPassword);
    }

    public static function get(): self
    {
        if (self::$instance === null) {
            self::$instance = new Connection();
        }

        return self::$instance;
    }

    public function getConnection(): \PDO
    {
        return $this->conn;
    }
}