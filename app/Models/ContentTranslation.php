<?php

namespace App\Models;

use App\Enums\TranslationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentTranslation extends Model
{
    use HasFactory;

    protected $table = 'translations';

    protected $fillable = [
        'content_id',
        'target_language',
        'translated_content',
        'status',
    ];

    protected $casts = [
        'translated_content' => 'array',
        'status' => TranslationStatus::class,
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    public function isCompleted(): bool
    {
        return $this->status instanceof TranslationStatus
            ? $this->status->isCompleted()
            : $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status instanceof TranslationStatus
            ? $this->status->isFailed()
            : $this->status === 'failed';
    }
}
