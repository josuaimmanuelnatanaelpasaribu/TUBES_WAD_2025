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
        Schema::table('users', function (Blueprint $table) {
            $table->string('preferred_translation_edition_identifier')->nullable()->after('password');
            // Tambahkan foreign key constraint jika Anda ingin memastikan identifier ada di available_editions
            // $table->foreign('preferred_translation_edition_identifier')
            //       ->references('api_edition_identifier')->on('available_editions')
            //       ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hapus foreign key dulu jika Anda menambahkannya
            // $table->dropForeign(['preferred_translation_edition_identifier']);
            $table->dropColumn('preferred_translation_edition_identifier');
        });
    }
};
