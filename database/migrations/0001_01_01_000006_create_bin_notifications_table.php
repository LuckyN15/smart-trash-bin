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
        Schema::create('bin_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bin_id')->nullable()->constrained('bins')->nullOnDelete();
            $table->enum('type', ['bin_full', 'offline', 'low_battery', 'classification_failed', 'setting_change', 'device_alert', 'custom'])->default('custom');
            $table->string('title');
            $table->text('message');
            $table->enum('level', ['info', 'warning', 'danger', 'success'])->default('info');
            $table->enum('status', ['unread', 'read'])->default('unread');
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bin_notifications');
    }
};
