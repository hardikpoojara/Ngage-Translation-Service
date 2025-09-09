<?php

namespace App\Repositories\Contracts;

use App\Models\ContentTranslation;
use App\Enums\TranslationStatus;
use Illuminate\Pagination\LengthAwarePaginator;

interface ContentTranslationRepositoryInterface
{
    public function create(array $data): ContentTranslation;
    public function findById(int $id): ?ContentTranslation;
    public function updateTranslation(ContentTranslation $translation, array $translatedContent): bool;
    public function markAsCompleted(ContentTranslation $translation): bool;
    public function markAsFailed(ContentTranslation $translation, string $reason): bool;
    public function findByStatus(TranslationStatus $status): \Illuminate\Database\Eloquent\Collection;
    public function getPaginated(
        int $perPage = 15,
        ?\App\Enums\TranslationStatus $status = null,
        ?string $sourceLanguage = null,
        ?string $targetLanguage = null
    ): LengthAwarePaginator;
}
