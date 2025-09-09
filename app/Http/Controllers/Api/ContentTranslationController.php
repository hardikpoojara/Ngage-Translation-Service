<?php

namespace App\Http\Controllers\Api;

use App\Enums\Language;
use App\Enums\TranslationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContentTranslationRequest;
use App\Http\Resources\ContentTranslationResource;
use App\Jobs\TranslateContentJob;
use App\Repositories\Contracts\ContentTranslationRepositoryInterface;
use App\Events\ContentTranslationRequested;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentTranslationController extends Controller
{
    public function __construct(
        private ContentTranslationRepositoryInterface $repository
    ) {}

    public function store(StoreContentTranslationRequest $request): JsonResponse
    {
        try {
            $payload = $request->validated();
            $sourceLanguage = $request->input('source_language', Language::getDefaultLanguage()->value);
            $targetLanguage = $request->input('target_language');

            $payload['original_content'] = [
                'name' => $payload['name'],
                'title' => $payload['title'],
                'description' => $payload['description'],
                'source_language' => $sourceLanguage,
            ];

            $translation = $this->repository->create($payload);

            TranslateContentJob::dispatch($translation, $targetLanguage);

            event(new ContentTranslationRequested($translation));

            return response()->json([
                'message' => 'Content translation request submitted successfully',
                'data' => new ContentTranslationResource($translation)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to submit translation request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        $translation = $this->repository->findById($id);

        if (!$translation) {
            return response()->json([
                'message' => 'Translation not found'
            ], 404);
        }

        return response()->json([
            'data' => new ContentTranslationResource($translation)
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $statusEnum = null;
        if ($request->filled('status')) {
            try {
                $statusEnum = TranslationStatus::from($request->string('status')->toString());
            } catch (\ValueError $e) {
                $statusEnum = null;
            }
        }

        $sourceLanguage = $request->string('source_language')->toString() ?: null;
        $targetLanguage = $request->string('target_language')->toString() ?: null;

        $translations = $this->repository->getPaginated(
            $request->integer('per_page', 15),
            $statusEnum,
            $sourceLanguage,
            $targetLanguage
        );

        return response()->json([
            'data' => ContentTranslationResource::collection($translations),
            'meta' => [
                'current_page' => $translations->currentPage(),
                'last_page'    => $translations->lastPage(),
                'per_page'     => $translations->perPage(),
                'total'        => $translations->total(),
            ],
        ]);
    }

}
