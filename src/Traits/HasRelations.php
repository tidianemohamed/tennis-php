<?php

namespace App\Traits;

trait HasRelations
{
    public function hasMany(string $related, string $foreignKey)
    {
        return $related::where($foreignKey, $this->id);
    }

    public function belongsTo(string $related, string $foreignKey)
    {
        return $related::find($this->$foreignKey);
    }
}