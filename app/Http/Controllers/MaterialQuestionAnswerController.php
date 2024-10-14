<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MaterialQuestionAnswer;
use Illuminate\Http\Request;

class MaterialQuestionAnswerController extends Controller
{
    public function index()
    {
        return MaterialQuestionAnswer::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([

        ]);

        return MaterialQuestionAnswer::create($data);
    }

    public function show(MaterialQuestionAnswer $materialQuestionAnswer)
    {
        return $materialQuestionAnswer;
    }

    public function update(Request $request, MaterialQuestionAnswer $materialQuestionAnswer)
    {
        $data = $request->validate([

        ]);

        $materialQuestionAnswer->update($data);

        return $materialQuestionAnswer;
    }

    public function destroy(MaterialQuestionAnswer $materialQuestionAnswer)
    {
        $materialQuestionAnswer->delete();

        return response()->json();
    }
}
