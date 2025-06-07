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
        Schema::create('juz_reading_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('juz_number'); // 1-30
            $table->unsignedTinyInteger('progress_percentage')->default(0); // 0-100
            $table->boolean('is_completed')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'juz_number']); // Setiap user punya satu progress per Juz
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('juz_reading_progress');
    }
};
