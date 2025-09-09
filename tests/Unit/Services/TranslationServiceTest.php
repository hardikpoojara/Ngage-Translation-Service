<?php
namespace Tests\Unit\Services;

use App\Services\TranslationService;
use App\Services\OpenAI\OpenAIConnector;
use App\Services\OpenAI\Requests\ChatCompletionRequest;
use App\Enums\Language;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Mockery;
use Saloon\Http\Response;
use Saloon\Exceptions\Request\RequestException;
use Illuminate\Support\Facades\Log;

class TranslationServiceTest extends TestCase
{
    protected $connector;
    protected $translationService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock of the connector
        $this->connector = Mockery::mock(OpenAIConnector::class);

        // Inject it into your service (assuming your service depends on it)
        $this->translationService = new TranslationService($this->connector);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_successfully_translates_content()
    {
        $content = [
            'name' => 'Test Product',
            'title' => 'Amazing Product',
            'description' => 'This is a great product'
        ];

        $translatedResponse = json_encode([
            'name' => 'Producto de Prueba',
            'title' => 'Producto Increíble',
            'description' => 'Este es un gran producto'
        ]);

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('json')
            ->with('choices.0.message.content')
            ->andReturn($translatedResponse);

        $this->connector
            ->shouldReceive('send')
            ->once()
            ->with(Mockery::type(ChatCompletionRequest::class))
            ->andReturn($response);

        $result = $this->translationService->translateContent($content, Language::ES);

        $this->assertEquals([
            'name' => 'Producto de Prueba',
            'title' => 'Producto Increíble',
            'description' => 'Este es un gran producto'
        ], $result);
    }

    #[Test]
    public function test_it_handles_malformed_json_response_with_fallback()
    {
        $content = [
            'name' => 'Test Product',
            'title' => 'Amazing Product',
            'description' => 'This is a test product.'
        ];

        $malformed = '{invalid json}';

        $response = Mockery::mock(\Saloon\Http\Response::class);
        $response->shouldReceive('json')
            ->once()
            ->with('choices.0.message.content')
            ->andReturn($malformed);

        $this->connector->shouldReceive('send')
            ->once()
            ->andReturn($response);

        \Illuminate\Support\Facades\Log::shouldReceive('warning')
            ->once()
            ->with(
                'Failed to parse translation response as JSON',
                Mockery::on(fn ($context) =>
                    isset($context['response']) &&
                    $context['response'] === '{invalid json}' &&
                    isset($context['json_error'])
                )
            );

        $result = $this->translationService->translateContent($content, 'es');

        $this->assertEquals([
            'name' => 'Translation unavailable for name',
            'title' => 'Translation unavailable for title',
            'description' => 'Translation unavailable for description',
        ], $result);
    }



    #[Test]
    public function it_throws_exception_on_request_failure()
    {
        $content = ['name' => 'Test'];
        $response = Mockery::mock(Response::class);
        $exception = new RequestException($response, 'Translation service temporarily unavailable: API Error');

        $this->connector
            ->shouldReceive('send')
            ->once()
            ->andThrow($exception);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Translation service temporarily unavailable: API Error');

        $this->translationService->translateContent($content, Language::FR);
    }

    #[Test]
    public function it_handles_string_target_language()
    {
        $content = ['name' => 'Test'];
        $translatedResponse = '{"name": "Test"}';

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('json')
            ->with('choices.0.message.content')
            ->andReturn($translatedResponse);

        $this->connector
            ->shouldReceive('send')
            ->once()
            ->andReturn($response);

        $result = $this->translationService->translateContent($content, Language::ES->value);;

        $this->assertEquals(['name' => 'Test'], $result);
    }

    #[Test]
    public function it_handles_null_target_language_with_default()
    {
        $content = ['name' => 'Test'];
        $translatedResponse = '{"name": "Test"}';

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('json')
            ->with('choices.0.message.content')
            ->andReturn($translatedResponse);

        $this->connector
            ->shouldReceive('send')
            ->once()
            ->andReturn($response);

        $result = $this->translationService->translateContent($content, null);

        $this->assertEquals(['name' => 'Test'], $result);
    }

    #[Test]
    public function it_validates_and_fills_missing_fields_in_response()
    {
        $content = [
            'name' => 'Original Name',
            'title' => 'Original Title',
            'description' => 'Original Description'
        ];

        $partialResponse = json_encode([
            'name' => 'Translated Name',
            'title' => 'Translated Title'
            // description missing
        ]);

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('json')
            ->with('choices.0.message.content')
            ->andReturn($partialResponse);

        $this->connector
            ->shouldReceive('send')
            ->once()
            ->andReturn($response);

        $result = $this->translationService->translateContent($content, Language::ES);

        $this->assertEquals([
            'name' => 'Translated Name',
            'title' => 'Translated Title',
            'description' => 'Original Description' // Fallback to original
        ], $result);
    }
}
