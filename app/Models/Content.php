<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Content extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_content',
        'source_language',
    ];

    protected $casts = [
        'original_content' => 'array',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(ContentTranslation::class, 'content_id');
    }
}
