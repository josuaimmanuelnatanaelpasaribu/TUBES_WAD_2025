<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Bookmark extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'api_ayat_identifier',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
