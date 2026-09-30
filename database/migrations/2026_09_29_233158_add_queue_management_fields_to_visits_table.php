<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table): void {
            $table->string('queue_priority')->default('normal');
            $table->dateTime('called_at')->nullable();
            $table->foreignId('called_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('no_show_at')->nullable();
            $table->foreignId('no_show_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index(['clinic_id', 'visited_at', 'status', 'queue_priority'], 'visits_queue_lookup_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table): void {
            $table->dropIndex('visits_queue_lookup_index');
            $table->dropConstrainedForeignId('called_by');
            $table->dropConstrainedForeignId('no_show_by');
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn([
                'queue_priority',
                'called_at',
                'no_show_at',
                'cancelled_at',
            ]);
        });
    }
};
