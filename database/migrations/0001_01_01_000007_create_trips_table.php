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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('driver_name')->nullable();
            $table->date('trip_date')->nullable();
            $table->unsignedSmallInteger('bins_served')->default(0);
            $table->decimal('weight_kg', 8, 2)->unsigned()->default(0);
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->boolean('on_time')->default(true);
            $table->boolean('completed')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
