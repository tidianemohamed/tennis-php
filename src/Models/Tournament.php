<?php

namespace App\Models;

class Tournament extends BaseModel
{
    protected static string $table = 'tournaments';

    public ?int $id = null;
    public string $name;
    public string $status;
    public $date;     // <--- Aggiunta
    public $location; // <--- Aggiunta
    public ?int $winner_player_id = null;
}