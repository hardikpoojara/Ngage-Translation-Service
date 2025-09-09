<?php

namespace App\Services\OpenAI\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Traits\Body\HasJsonBody;

class ChatCompletionRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private string $prompt,
        private string $model = 'gpt-3.5-turbo',
        private float $temperature = 0.3,
        private int $maxTokens = 2000
    ) {}

    public function resolveEndpoint(): string
    {
        return '/chat/completions';
    }

    protected function defaultBody(): array
    {
        return [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a professional translator. Translate the given content accurately while maintaining the original meaning, tone, and context.'
                ],
                [
                    'role' => 'user',
                    'content' => $this->prompt
                ]
            ],
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
        ];
    }
}
