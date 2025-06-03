<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoriteSurat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'surat_number'
    ];

    /**
     * Get the user that owns the favorite surat.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 