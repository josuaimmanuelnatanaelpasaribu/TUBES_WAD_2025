<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_ayat_identifier',
        'content',
        'admin_id',
    ];

    // Jika Anda ingin relasi ke User (Admin)
    // public function admin()
    // {
    //     return $this->belongsTo(User::class, 'admin_id');
    // }
}
