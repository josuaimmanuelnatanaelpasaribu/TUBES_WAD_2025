<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvailableEdition extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_edition_identifier',
        'name',
        'language_name',
        'is_active_for_users',
        'type',
        'qari_name',
        'style'
    ];

    protected $casts = [
        'is_active_for_users' => 'boolean',
    ];

    public function scopeAudio($query)
    {
        return $query->where('type', 'audio');
    }

    public function scopeTranslation($query)
    {
        return $query->where('type', 'translation');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active_for_users', true);
    }
}
