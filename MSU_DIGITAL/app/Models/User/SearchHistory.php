<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class SearchHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'query',
        'label',
        'is_bookmarked',
        // 'timestamp' diatur oleh database, tidak perlu fillable
    ];

    public $timestamps = false; // Karena kita menggunakan kolom `timestamp` tunggal

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
