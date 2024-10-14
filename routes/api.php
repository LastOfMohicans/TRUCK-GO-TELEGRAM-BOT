<?php

use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\TelegramController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/webhook/client', [TelegramController::class, 'startClientBot']);
Route::get('/telegram/client/register_commands', [TelegramController::class, 'registerClientCommands']);

Route::post('/webhook/vendor', [TelegramController::class, 'startVendorBot']);
Route::get('/telegram/vendor/register_commands', [TelegramController::class, 'registerVendorCommands']);

Route::get('/catalog/{catalog_id}/material_with_questions',[CatalogController::class, 'getMaterialWithQuestionByCatalogID']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::middleware(['auth:sanctum'])
    ->controller(OrderController::class)
    ->group(function () {
        Route::post('/order', 'create');
        Route::get('/questions', 'getQuestions');
    });

Route::get('/catalog/{catalog_id}/children', [CatalogController::class, 'getChildren']);
