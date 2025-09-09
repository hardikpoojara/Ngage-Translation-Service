<?php

namespace App\Services;

use App\Enums\Language;
use App\Services\OpenAI\OpenAIConnector;
use App\Services\OpenAI\Requests\ChatCompletionRequest;
use Illuminate\Support\Facades\Log;
use Saloon\Exceptions\Request\RequestException;

class TranslationService
{
    public function __construct(
        private OpenAIConnector $openAI
    ) {}

    public function translateContent(array $content, Language|string|null $targetLanguage = null): array
    {
        try {
            $prompt = $this->buildTranslationPrompt($content, $targetLanguage);

            $request = new ChatCompletionRequest($prompt);
            $response = $this->openAI->send($request);

            $translatedText = $response->json('choices.0.message.content');

            return $this->parseTranslationResponse($translatedText, $content);

        } catch (RequestException $e) {
            Log::error('OpenAI translation request failed', [
                'error' => $e->getMessage(),
                'content' => $content,
            ]);

            throw new \Exception('Translation service temporarily unavailable: ' . $e->getMessage());
        }
    }

    private function buildTranslationPrompt(array $content, Language|string|null $targetLanguage = null): string
    {
        $targetLanguageName = match (true) {
            $targetLanguage instanceof Language => $targetLanguage->label(),
            is_string($targetLanguage) && Language::tryFromCode($targetLanguage) instanceof Language => Language::tryFromCode($targetLanguage)->label(),
            is_string($targetLanguage) => Language::nameFor($targetLanguage),
            default => Language::getDefaultLanguage()->label(),
        };

        $prompt = "Please translate the following content to {$targetLanguageName}. ";
        $prompt .= "Return the translation as a JSON object with the same structure as the input.\n\n";

        $prompt .= "Content to translate:\n";
        $prompt .= json_encode($content, JSON_PRETTY_PRINT);
        $prompt .= "\n\nIMPORTANT: Return only valid JSON in your response.";

        return $prompt;
    }

    private function parseTranslationResponse(string $response, array $originalContent): array
    {
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');

        if ($jsonStart === false || $jsonEnd === false) {
            throw new \Exception('Invalid response format from translation service');
        }

        $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        $decoded = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Failed to parse translation response as JSON', [
                'response' => $response,
                'json_error' => json_last_error_msg()
            ]);

            return [
                'name' => $this->extractTranslationFallback($response, 'name'),
                'title' => $this->extractTranslationFallback($response, 'title'),
                'description' => $this->extractTranslationFallback($response, 'description')
            ];
        }

        // Validate that all required fields are present
        foreach (array_keys($originalContent) as $field) {
            if (!isset($decoded[$field])) {
                $decoded[$field] = $originalContent[$field]; // Fallback to original
            }
        }

        return $decoded;
    }

    private function extractTranslationFallback(string $response, string $field): string
    {
        $lines = explode("\n", $response);
        foreach ($lines as $line) {
            if (stripos($line, $field) !== false) {
                return trim(str_replace([$field, ':', '"', "'"], '', $line));
            }
        }
        return "Translation unavailable for {$field}";
    }
}
