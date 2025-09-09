<?php

namespace Tests\Unit\Services\OpenAI;

use App\Services\OpenAI\OpenAIConnector;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OpenAIConnectorTest extends TestCase
{
    #[Test]
    public function it_has_correct_base_url()
    {
        $connector = new OpenAIConnector();

        $this->assertEquals('https://api.openai.com/v1', $connector->resolveBaseUrl());
    }

    #[Test]
    public function it_includes_authorization_header()
    {
        config(['services.openai.api_key' => 'test-api-key']);

        $connector = new OpenAIConnector();
        $headers = $this->getPrivateProperty($connector, 'defaultHeaders')();

        $this->assertEquals('Bearer test-api-key', $headers['Authorization']);
        $this->assertEquals('application/json', $headers['Content-Type']);
    }

    #[Test]
    public function it_has_correct_timeout_configuration()
    {
        $connector = new OpenAIConnector();
        $config = $this->getPrivateProperty($connector, 'defaultConfig')();

        $this->assertEquals(30, $config['timeout']);
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
