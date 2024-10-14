<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\OrderQuestionAnswer;
use Illuminate\Http\Request;

class OrderQuestionAnswerController extends Controller
{
    public function index()
    {
        return OrderQuestionAnswer::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([

        ]);

        return OrderQuestionAnswer::create($data);
    }

    public function show(OrderQuestionAnswer $orderQuestionAnswer)
    {
        return $orderQuestionAnswer;
    }

    public function update(Request $request, OrderQuestionAnswer $orderQuestionAnswer)
    {
        $data = $request->validate([

        ]);

        $orderQuestionAnswer->update($data);

        return $orderQuestionAnswer;
    }

    public function destroy(OrderQuestionAnswer $orderQuestionAnswer)
    {
        $orderQuestionAnswer->delete();

        return response()->json();
    }
}
