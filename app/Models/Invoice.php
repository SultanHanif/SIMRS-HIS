<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['visit_id', 'amount', 'amount_received', 'change_amount', 'status', 'payment_method', 'payment_reference', 'payment_notes', 'paid_by', 'paid_at'])]
class Invoice extends Model
{
    protected function casts(): array
    {
        return ['paid_at' => 'datetime'];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
