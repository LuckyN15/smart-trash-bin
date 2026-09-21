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
        Schema::create('bin_compartments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bin_id')->constrained('bins')->cascadeOnDelete();
            $table->enum('category', ['organik', 'anorganik', 'b3'])->default('organik');
            $table->unsignedTinyInteger('capacity_percent')->default(0);
            $table->enum('status', ['empty', 'ok', 'near', 'full'])->default('ok');
            $table->unique(['bin_id', 'category']);
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('classifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bin_compartment_id')->constrained('bin_compartments')->cascadeOnDelete();
            $table->enum('category', ['organik', 'anorganik', 'b3'])->default('organik');
            $table->unsignedTinyInteger('confidence')->default(0);
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->string('model_version')->default('v1');
            $table->timestamp('detected_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classifications');
        Schema::dropIfExists('bin_compartments');
    }
};
