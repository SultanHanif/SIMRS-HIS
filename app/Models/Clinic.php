<?php

namespace App\Models;

use Database\Factories\ClinicFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use LogicException;

#[Fillable(['code', 'name', 'consultation_fee', 'status'])]
class Clinic extends Model
{
    /** @use HasFactory<ClinicFactory> */
    use HasFactory;

    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function allocateQueueNumberForDate(string $queueDate): int
    {
        if (! $this->exists) {
            throw new LogicException('A saved clinic is required to allocate a queue number.');
        }

        return DB::transaction(function () use ($queueDate): int {
            DB::table('daily_queue_counters')->insertOrIgnore([
                'clinic_id' => $this->id,
                'queue_date' => $queueDate,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $counter = DB::table('daily_queue_counters')
                ->where('clinic_id', $this->id)
                ->where('queue_date', $queueDate)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                throw new LogicException('Daily queue counter could not be initialized.');
            }

            $queueNumber = $counter->last_number + 1;

            DB::table('daily_queue_counters')
                ->where('id', $counter->id)
                ->update([
                    'last_number' => $queueNumber,
                    'updated_at' => now(),
                ]);

            return $queueNumber;
        }, attempts: 5);
    }

    protected function casts(): array
    {
        return ['consultation_fee' => 'integer'];
    }
}
