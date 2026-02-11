<?php

namespace App\Models;

class Player extends BaseModel
{
    protected static string $table = 'players'; 

    public ?int $id = null;
    // Rimuovi o tieni $name solo come fallback se non hai ancora migrato tutti i dati
    public ?string $first_name = null;
    public ?string $last_name = null;
    public ?string $country = null;
    public ?int $tournament_id = null;
    public ?string $deleted_at = null;

    /**
     * Soft Delete
     */
    public function softDelete(): void
    {
        $this->deleted_at = date('Y-m-d H:i:s');
        $this->save();
    }
}
