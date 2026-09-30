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
        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('payment_method', 32)->nullable()->after('status');
            $table->unsignedBigInteger('amount_received')->nullable()->after('amount');
            $table->unsignedBigInteger('change_amount')->nullable()->after('amount_received');
            $table->string('payment_reference', 100)->nullable()->after('payment_method');
            $table->text('payment_notes')->nullable()->after('payment_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn([
                'payment_method',
                'amount_received',
                'change_amount',
                'payment_reference',
                'payment_notes',
            ]);
        });
    }
};
