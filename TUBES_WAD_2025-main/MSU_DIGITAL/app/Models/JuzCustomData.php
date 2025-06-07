<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JuzCustomData extends Model
{
    use HasFactory;

    protected $fillable = [
        'juz_number',
        'custom_description',
    ];
}
