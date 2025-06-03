<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratCustomData extends Model
{
    use HasFactory;

    protected $fillable = [
        'surat_number',
        'custom_description',
    ];

    // Jika ada relasi ke model Surah (jika ada model Surah terpisah)
    // public function surah()
    // {
    //     return $this->belongsTo(Surah::class, 'surat_number', 'number');
    // }
}
