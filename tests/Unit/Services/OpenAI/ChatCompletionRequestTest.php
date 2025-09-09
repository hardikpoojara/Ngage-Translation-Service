<?php

namespace Tests\Unit\Services\OpenAI;

use App\Services\OpenAI\Requests\ChatCompletionRequest;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Enums\Method;
use Tests\TestCase;

class ChatCompletionRequestTest extends TestCase
{
    #[Test]
    public function it_has_correct_method_and_endpoint()
    {
        $request = new ChatCompletionRequest('Test prompt');

        $this->assertEquals(Method::POST, $request->getMethod());
        $this->assertEquals('/chat/completions', $request->resolveEndpoint());
    }

    #[Test]
    public function it_builds_correct_request_body_with_defaults()
    {
        $prompt = 'Translate this text';
        $request = new ChatCompletionRequest($prompt);

        $body = $this->getPrivateProperty($request, 'defaultBody')();

        $this->assertEquals('gpt-3.5-turbo', $body['model']);
        $this->assertEquals(0.3, $body['temperature']);
        $this->assertEquals(2000, $body['max_tokens']);
        $this->assertCount(2, $body['messages']);
        $this->assertEquals('system', $body['messages'][0]['role']);
        $this->assertEquals('user', $body['messages'][1]['role']);
        $this->assertEquals($prompt, $body['messages'][1]['content']);
    }

    #[Test]
    public function it_builds_request_body_with_custom_parameters()
    {
        $prompt = 'Custom prompt';
        $model = 'gpt-4';
        $temperature = 0.7;
        $maxTokens = 1500;

        $request = new ChatCompletionRequest($prompt, $model, $temperature, $maxTokens);
        $body = $this->getPrivateProperty($request, 'defaultBody')();

        $this->assertEquals($model, $body['model']);
        $this->assertEquals($temperature, $body['temperature']);
        $this->assertEquals($maxTokens, $body['max_tokens']);
        $this->assertEquals($prompt, $body['messages'][1]['content']);
    }

    #[Test]
    public function it_includes_system_message_for_translation_context()
    {
        $request = new ChatCompletionRequest('Test');
        $body = $this->getPrivateProperty($request, 'defaultBody')();

        $systemMessage = $body['messages'][0]['content'];
        $this->assertStringContainsString('professional translator', $systemMessage);
        $this->assertStringContainsString('accurately', $systemMessage);
        $this->assertStringContainsString('original meaning', $systemMessage);
    }

    private function getPrivateProperty($object, $methodName)
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return function () use ($method, $object) {
            return $method->invoke($object);
        };
    }
}
