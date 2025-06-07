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
        Schema::create('global_keywords', function (Blueprint $table) {
            $table->id();
            $table->string('keyword');
            $table->string('api_entity_identifier'); // misal "surah:2" atau "ayat:2:255"
            $table->string('entity_type'); // 'surah' atau 'ayat'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_keywords');
    }
};
