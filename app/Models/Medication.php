<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'strength', 'dosage_form', 'unit_price', 'status'])]
class Medication extends Model
{
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function displayName(): string
    {
        return "{$this->name} {$this->strength} ({$this->dosage_form})";
    }
}
