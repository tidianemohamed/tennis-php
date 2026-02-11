<?php

namespace App\Database;

use PDO;
use PDOException;

class DB
{
    private static ?DB $instance = null;
    private PDO $connection;

    private function __construct()
    {
        // Il percorso deve salire di due livelli per uscire da src/Database e trovare config/
        $config = require __DIR__ . '/../../config/database.php';

        try {
            $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
            
            $this->connection = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("Errore di connessione al database: " . $e->getMessage());
        }
    }

    public static function getInstance(): DB
    {
        if (self::$instance === null) {
            self::$instance = new DB();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}