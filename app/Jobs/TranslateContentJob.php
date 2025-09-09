<?php

namespace App\Jobs;

use App\Enums\Language;
use App\Models\ContentTranslation;
use App\Services\TranslationService;
use App\Repositories\Contracts\ContentTranslationRepositoryInterface;
use App\Events\ContentTranslationCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class TranslateContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 2;
    public int $timeout = 120;

    public function __construct(
        private ContentTranslation $translation,
        private readonly Language|string|null $targetLanguage = null
    ) {}

    public function handle(
        TranslationService $translationService,
        ContentTranslationRepositoryInterface $repository
    ): void {
        try {
            $content = $this->translation->content?->original_content ?? [];

            $translatedContent = $translationService->translateContent(
                $content,
                is_string($this->targetLanguage)
                    ? $this->targetLanguage
                    : ($this->targetLanguage instanceof Language ? $this->targetLanguage : (isset($this->translation->target_language) ? Language::tryFromCode($this->translation->target_language) ?? $this->translation->target_language : null))
            );

            $repository->updateTranslation($this->translation, $translatedContent);
            $repository->markAsCompleted($this->translation);

            event(new ContentTranslationCompleted($this->translation));

        } catch (Throwable $e) {
            $this->handleTranslationFailure($e, $repository);
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Translation job failed permanently', [
            'translation_id' => $this->translation->id,
            'exception' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        app(ContentTranslationRepositoryInterface::class)
            ->markAsFailed($this->translation, $exception->getMessage());
    }

    private function handleTranslationFailure(Throwable $e, ContentTranslationRepositoryInterface $repository): void
    {
        Log::error('Translation attempt failed', [
            'translation_id' => $this->translation->id,
            'attempt' => $this->attempts(),
            'error' => $e->getMessage()
        ]);

        if ($this->attempts() >= $this->tries) {
            $repository->markAsFailed($this->translation, $e->getMessage());
        }

        throw $e; // Re-throw to trigger retry mechanism
    }
}
