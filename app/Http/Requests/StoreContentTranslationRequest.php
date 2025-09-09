<?php

namespace App\Http\Requests;

use App\Enums\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreContentTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:500'],
            'description' => ['required', 'string', 'max:2000'],
            'target_language' => ['required', new Enum(Language::class), 'string', 'max:5'],
            'source_language' => ['nullable', new Enum(Language::class), 'string', 'max:5']
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'The :attribute is required.',
            'string' => 'The :attribute must be a valid string.',
            'max' => 'The :attribute may not be greater than :max characters.',
            'target_language.required' => 'The target language is required.',
            'source_language.required' => 'The source language is required.'
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'content name',
            'title' => 'content title',
            'description' => 'content description',
            'target_language' => 'target language',
            'source_language' => 'source language'
        ];
    }
}
