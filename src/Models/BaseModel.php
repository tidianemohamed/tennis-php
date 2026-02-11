<?php

namespace App\Models;

use PDO;
use App\Database\DB;

/**
 * Classe astratta BaseModel
 * Fornisce le funzionalità CRUD avanzate per tutti i modelli.
 */
abstract class BaseModel
{
    protected static string $table;
    public ?int $id = null;

    protected static function getDB(): PDO
    {
        return DB::getInstance()->getConnection();
    }

    /**
     * Recupera tutti i record
     */
    public static function all(): array
    {
        $db = self::getDB();
        $stmt = $db->query("SELECT * FROM " . static::$table . " ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_CLASS, static::class);
    }

    /**
     * Trova un record per ID
     */
    public static function find(int $id): ?static
    {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT * FROM " . static::$table . " WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, static::class);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Filtra i record (es: TournamentMatch::where('tournament_id', 11))
     */
    public static function where(string $column, $value): array
    {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT * FROM " . static::$table . " WHERE $column = :val ORDER BY id ASC");
        $stmt->execute(['val' => $value]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, static::class);
    }

    /**
     * Crea un record da un array (Metodo statico di utilità)
     */
    public static function create(array $data): ?static
    {
        $instance = new static();
        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                $instance->$key = $value;
            }
        }
        return $instance->save() ? $instance : null;
    }

    /**
     * Salva il record: fa INSERT se l'id è nullo, altrimenti fa UPDATE
     */
    public function save(): bool
    {
        $db = self::getDB();
        $properties = get_object_vars($this);
        
        // Se l'ID non c'è, procediamo con l'INSERT
        if (!$this->id) {
            unset($properties['id']);
            $columns = implode(', ', array_keys($properties));
            $placeholders = ':' . implode(', :', array_keys($properties));
            
            // Usiamo RETURNING id per PostgreSQL/Beekeeper
            $sql = "INSERT INTO " . static::$table . " ($columns) VALUES ($placeholders) RETURNING id";
            
            $stmt = $db->prepare($sql);
            if ($stmt->execute($properties)) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $this->id = (int)($result['id'] ?? $db->lastInsertId());
                return true;
            }
            return false;
        } 
        
        // Se l'ID c'è, procediamo con l'UPDATE
        else {
            $id = $this->id;
            unset($properties['id']);
            $sets = [];
            $params = ['id_val' => $id];

            foreach ($properties as $key => $value) {
                $sets[] = "$key = :$key";
                $params[$key] = $value;
            }

            $sql = "UPDATE " . static::$table . " SET " . implode(', ', $sets) . " WHERE id = :id_val";
            return $db->prepare($sql)->execute($params);
        }
    }
}