<?php

namespace App\Models;

use Database\Factories\VisitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['visit_number', 'patient_id', 'doctor_id', 'clinic_id', 'registered_by', 'visited_at', 'queue_date', 'queue_number', 'visit_type', 'payment_method', 'complaint', 'status', 'queue_priority', 'called_at', 'called_by', 'no_show_at', 'no_show_by', 'cancelled_at', 'cancelled_by'])]
class Visit extends Model
{
    /** @use HasFactory<VisitFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
            'queue_date' => 'date',
            'queue_number' => 'integer',
            'called_at' => 'datetime',
            'no_show_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function calledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'called_by');
    }

    public function noShowBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'no_show_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function examination(): HasOne
    {
        return $this->hasOne(Examination::class);
    }

    public function prescription(): HasOne
    {
        return $this->hasOne(Prescription::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function formattedQueueNumber(): string
    {
        return $this->queue_number === null
            ? '—'
            : str_pad((string) $this->queue_number, 3, '0', STR_PAD_LEFT);
    }
}
