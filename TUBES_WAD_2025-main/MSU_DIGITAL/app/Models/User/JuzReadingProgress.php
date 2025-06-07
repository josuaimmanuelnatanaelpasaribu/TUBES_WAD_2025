<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class JuzReadingProgress extends Model
{
    use HasFactory;

    protected $table = 'juz_reading_progress'; // Eksplisit nama tabel

    protected $fillable = [
        'user_id',
        'juz_number',
        'progress_percentage',
        'is_completed',
    ];

    /**
     * Relasi ke User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
