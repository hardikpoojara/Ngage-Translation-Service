<?php

namespace Tests\Unit\Jobs;

use App\Enums\Language;
use App\Jobs\TranslateContentJob;
use App\Models\ContentTranslation;
use App\Services\TranslationService;
use App\Repositories\Contracts\ContentTranslationRepositoryInterface;
use App\Events\ContentTranslationCompleted;
use Tests\TestCase;
use Mockery;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;

class TranslateContentJobTest extends TestCase
{
    private $translationService;
    private $repository;
    private $translation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translationService = Mockery::mock(TranslationService::class);
        $this->repository = Mockery::mock(ContentTranslationRepositoryInterface::class);

        // Fake content data
        $content = new \stdClass();
        $content->original_content = [
            'name' => 'Test Name',
            'title' => 'Test Title',
            'description' => 'Test Description'
        ];

        // Use actual ContentTranslation model instead of stdClass
        $this->translation = new ContentTranslation();
        $this->translation->id = 1;
        $this->translation->target_language = 'es';
        $this->translation->content = $content; // assign directly
    }

    #[Test]
    public function it_successfully_translates_content_with_language_enum()
    {
        Event::fake();

        $targetLanguage = 'es';
        $translatedContent = [
            'name' => 'Nombre de Prueba',
            'title' => 'Título de Prueba',
            'description' => 'Descripción de Prueba'
        ];

        $this->translationService
            ->shouldReceive('translateContent')
            ->once()
            ->with($this->translation->content->original_content, $targetLanguage)
            ->andReturn($translatedContent);

        $this->repository
            ->shouldReceive('updateTranslation')
            ->once()
            ->with($this->translation, $translatedContent);

        $this->repository
            ->shouldReceive('markAsCompleted')
            ->once()
            ->with($this->translation);

        $job = new TranslateContentJob($this->translation, $targetLanguage);
        $job->handle($this->translationService, $this->repository);

        Event::assertDispatched(ContentTranslationCompleted::class);
    }

    #[Test]
    public function it_successfully_translates_content_with_string_language()
    {
        Event::fake();

        $targetLanguage = 'fr';
        $translatedContent = [
            'name' => 'Nom de Test',
            'title' => 'Titre de Test',
            'description' => 'Description de Test'
        ];

        $this->translationService
            ->shouldReceive('translateContent')
            ->once()
            ->with($this->translation->content->original_content, $targetLanguage)
            ->andReturn($translatedContent);

        $this->repository
            ->shouldReceive('updateTranslation')
            ->once()
            ->with($this->translation, $translatedContent);

        $this->repository
            ->shouldReceive('markAsCompleted')
            ->once()
            ->with($this->translation);

        $job = new TranslateContentJob($this->translation, $targetLanguage);
        $job->handle($this->translationService, $this->repository);

        Event::assertDispatched(ContentTranslationCompleted::class);
    }

    #[Test]
    public function it_handles_translation_failure_and_retries()
    {
        $exception = new \Exception('Translation service error');

        $this->translationService
            ->shouldReceive('translateContent')
            ->once()
            ->andThrow($exception);

        Log::shouldReceive('error')
            ->once()
            ->with('Translation attempt failed', Mockery::on(function ($context) {
                return $context['translation_id'] === 1 &&
                    $context['error'] === 'Translation service error';
            }));

        $job = new TranslateContentJob($this->translation);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Translation service error');

        $job->handle($this->translationService, $this->repository);
    }

    #[Test]
    public function it_marks_translation_as_failed_when_max_attempts_reached()
    {
        $exception = new \Exception('Permanent failure');

        Log::shouldReceive('error')
            ->once()
            ->with('Translation job failed permanently', Mockery::on(function ($context) {
                return $context['translation_id'] === 1 &&
                    $context['exception'] === 'Permanent failure';
            }));

        $job = new TranslateContentJob($this->translation);
        $job->failed($exception);
    }

    #[Test]
    public function it_handles_null_content()
    {
        Event::fake();

        $this->translation->content = null;

        $expectedLang = Language::tryFromCode($this->translation->target_language);

        $this->translationService
            ->shouldReceive('translateContent')
            ->once()
            ->with([], $expectedLang)
            ->andReturn([]);

        $this->repository
            ->shouldReceive('updateTranslation')
            ->once()
            ->with($this->translation, []);

        $this->repository
            ->shouldReceive('markAsCompleted')
            ->once()
            ->with($this->translation);

        $job = new TranslateContentJob($this->translation);
        $job->handle($this->translationService, $this->repository);

        Event::assertDispatched(ContentTranslationCompleted::class);
    }
}
