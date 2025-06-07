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
        Schema::table('available_editions', function (Blueprint $table) {
            // Add new columns for audio editions if they don't exist
            if (!Schema::hasColumn('available_editions', 'type')) {
                $table->string('type')->default('translation'); // 'translation' or 'audio'
            }
            if (!Schema::hasColumn('available_editions', 'qari_name')) {
                $table->string('qari_name')->nullable();
            }
            if (!Schema::hasColumn('available_editions', 'style')) {
                $table->string('style')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('available_editions', function (Blueprint $table) {
            $table->dropColumn(['type', 'qari_name', 'style']);
        });
    }
}; 