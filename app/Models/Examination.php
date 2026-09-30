<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['visit_id', 'doctor_id', 'diagnosis_code', 'diagnosis', 'notes', 'plan', 'systolic_pressure', 'diastolic_pressure', 'temperature_c', 'pulse_rate', 'weight_kg', 'height_cm'])]
class Examination extends Model
{
    protected function casts(): array
    {
        return [
            'systolic_pressure' => 'integer',
            'diastolic_pressure' => 'integer',
            'temperature_c' => 'decimal:1',
            'pulse_rate' => 'integer',
            'weight_kg' => 'decimal:2',
            'height_cm' => 'decimal:2',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
