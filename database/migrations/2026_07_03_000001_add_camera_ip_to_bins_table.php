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
        Schema::table('bins', function (Blueprint $table) {
            if (!Schema::hasColumn('bins', 'camera_ip')) {
                $table->string('camera_ip', 150)->nullable()->after('area');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bins', function (Blueprint $table) {
            if (Schema::hasColumn('bins', 'camera_ip')) {
                $table->dropColumn('camera_ip');
            }
        });
    }
};
