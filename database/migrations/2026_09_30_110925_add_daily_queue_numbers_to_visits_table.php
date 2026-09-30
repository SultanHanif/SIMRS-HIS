<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table): void {
            $table->date('queue_date')->nullable()->after('visited_at');
            $table->unsignedInteger('queue_number')->nullable()->after('queue_date');
        });

        Schema::create('daily_queue_counters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->date('queue_date');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
            $table->unique(['clinic_id', 'queue_date'], 'daily_queue_counters_clinic_date_unique');
        });

        $queueDays = DB::table('visits')
            ->select('clinic_id')
            ->selectRaw('DATE(visited_at) as queue_date')
            ->groupBy('clinic_id')
            ->groupByRaw('DATE(visited_at)')
            ->orderBy('clinic_id')
            ->orderBy('queue_date')
            ->get();

        foreach ($queueDays as $queueDay) {
            $queueNumber = 0;
            $visitIds = DB::table('visits')
                ->where('clinic_id', $queueDay->clinic_id)
                ->whereDate('visited_at', $queueDay->queue_date)
                ->orderBy('visited_at')
                ->orderBy('id')
                ->pluck('id');

            foreach ($visitIds as $visitId) {
                $queueNumber++;

                DB::table('visits')
                    ->where('id', $visitId)
                    ->update([
                        'queue_date' => $queueDay->queue_date,
                        'queue_number' => $queueNumber,
                    ]);
            }

            DB::table('daily_queue_counters')->insert([
                'clinic_id' => $queueDay->clinic_id,
                'queue_date' => $queueDay->queue_date,
                'last_number' => $queueNumber,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('visits', function (Blueprint $table): void {
            $table->unique(
                ['clinic_id', 'queue_date', 'queue_number'],
                'visits_clinic_queue_date_number_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table): void {
            $table->dropUnique('visits_clinic_queue_date_number_unique');
            $table->dropColumn(['queue_date', 'queue_number']);
        });

        Schema::dropIfExists('daily_queue_counters');
    }
};
