<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CatalogService;
use App\Services\MaterialQuestionsService;
use App\Services\MaterialService;
use Illuminate\Http\JsonResponse;


class CatalogController extends Controller
{
    private CatalogService $catalogService;
    private MaterialService $materialService;
    private MaterialQuestionsService $materialQuestionsService;

    public function __construct(
        CatalogService  $catalogService,
        MaterialService $materialService,
        MaterialQuestionsService $materialQuestionsService
        )
    {
        $this->catalogService = $catalogService;
        $this->materialService = $materialService;
        $this->materialQuestionsService = $materialQuestionsService;
    }

    /**
     * Возвращаем детей каталога.
     *
     * @param int $catalog_id
     * @return JsonResponse
     */
    public function getChildren(int $catalog_id): JsonResponse
    {
        $children = $this->catalogService->getCatalogChildren($catalog_id);
        return response()->json($children);
    }

    /**
     * Находим материал по $catalogID и возвращаем со всеми вопросами в формате json объекта.
     *
     * @param int $catalogID
     * @return JsonResponse
     */
    public function getMaterialWithQuestionByCatalogID(int $catalogID): JsonResponse
    {
        $material = $this->materialService->firstMaterialByCatalogID($catalogID);
        $materialID = $material->id;
        $materialQuestions = $this->materialQuestionsService->getActiveQuestions($materialID);
        $data = ['material' => $material, 'questions' => $materialQuestions];

        return response()->json($data);
    }
}
