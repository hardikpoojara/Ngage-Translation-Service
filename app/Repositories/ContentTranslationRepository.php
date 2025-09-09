<?php

namespace App\Repositories;

use App\Enums\TranslationStatus;
use App\Filters\ContentTranslations\SourceLanguageFilter;
use App\Filters\ContentTranslations\TargetLanguageFilter;
use App\Filters\ContentTranslations\StatusFilter;
use App\Models\Content;
use App\Models\ContentTranslation;
use App\Repositories\Contracts\ContentTranslationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pipeline\Pipeline;

class ContentTranslationRepository implements ContentTranslationRepositoryInterface
{
    public function create(array $data): ContentTranslation
    {
        // Create Content first
        $content = Content::create([
            'original_content' => $data['original_content'],
            'source_language' => $data['source_language'],
        ]);

        $translation = ContentTranslation::create([
            'content_id' => $content->id,
            'target_language' => is_string($data['target_language']) ? $data['target_language'] : ($data['target_language']->value ?? null),
            'status' => TranslationStatus::PENDING,
        ]);

        return $translation->load('content');
    }

    public function findById(int $id): ?ContentTranslation
    {
        return ContentTranslation::with('content')->find($id);
    }

    public function updateTranslation(ContentTranslation $translation, array $translatedContent): bool
    {
        return $translation->update([
            'translated_content' => $translatedContent,
        ]);
    }

    public function markAsCompleted(ContentTranslation $translation): bool
    {
        return $translation->update(['status' => TranslationStatus::COMPLETED]);
    }

    public function markAsFailed(ContentTranslation $translation, string $reason): bool
    {
        // No failed_reason column per new schema; just set status
        return $translation->update([
            'status' => TranslationStatus::FAILED,
        ]);
    }

    public function findByStatus(TranslationStatus $status): Collection
    {
        return ContentTranslation::where('status', $status)->get();
    }

    public function getPaginated(
        int $perPage = 15,
        ?TranslationStatus $status = null,
        ?string $sourceLanguage = null,
        ?string $targetLanguage = null
    ): LengthAwarePaginator {
        $query = ContentTranslation::with('content')->latest();

        // Build and run the pipeline of filters
        $query = app(Pipeline::class)
            ->send($query)
            ->through([
                new SourceLanguageFilter($sourceLanguage),
                new TargetLanguageFilter($targetLanguage),
                new StatusFilter($status),
            ])
            ->thenReturn();

        return $query->paginate($perPage);
    }
}
