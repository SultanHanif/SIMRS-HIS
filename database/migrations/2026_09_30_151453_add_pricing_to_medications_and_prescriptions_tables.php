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
        Schema::table('medications', function (Blueprint $table): void {
            $table->unsignedBigInteger('unit_price')->default(0)->after('dosage_form');
        });

        Schema::table('prescriptions', function (Blueprint $table): void {
            $table->unsignedInteger('quantity')->default(1)->after('medication_name');
            $table->unsignedBigInteger('unit_price')->default(0)->after('quantity');
            $table->unsignedBigInteger('total_price')->default(0)->after('unit_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table): void {
            $table->dropColumn(['quantity', 'unit_price', 'total_price']);
        });

        Schema::table('medications', function (Blueprint $table): void {
            $table->dropColumn('unit_price');
        });
    }
};
