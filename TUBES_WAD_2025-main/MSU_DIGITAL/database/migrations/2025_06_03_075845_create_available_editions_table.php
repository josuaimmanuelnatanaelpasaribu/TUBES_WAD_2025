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
        Schema::create('available_editions', function (Blueprint $table) {
            $table->id();
            $table->string('api_edition_identifier')->unique(); // misal 'id.indonesian'
            $table->string('name'); // misal 'Bahasa Indonesia - Kemenag'
            $table->string('language_code'); // misal 'id'
            $table->string('type'); // misal 'translation', 'tafsir'
            $table->boolean('is_active_for_users')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('available_editions');
    }
};
