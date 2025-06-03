<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlobalKeyword extends Model
{
    use HasFactory;

    protected $fillable = [
        'keyword',
        'api_entity_identifier',
        'entity_type',
    ];
}
