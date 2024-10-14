<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MaterialQuestion;
use Illuminate\Http\Request;

class MaterialQuestionController extends Controller
{
    public function index()
    {
        return MaterialQuestion::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'material_id' => ['required', 'integer'],
            'question' => ['required'],
            'question_answer_type' => ['required'],
            'order' => ['required', 'integer'],
        ]);

        return MaterialQuestion::create($request->validated());
    }

    public function show(MaterialQuestion $materialQuestion)
    {
        return $materialQuestion;
    }

    public function update(Request $request, MaterialQuestion $materialQuestion)
    {
        $request->validate([
            'material_id' => ['required', 'integer'],
            'question' => ['required'],
            'question_answer_type' => ['required'],
            'order' => ['required', 'integer'],
        ]);

        $materialQuestion->update($request->validated());

        return $materialQuestion;
    }

    public function destroy(MaterialQuestion $materialQuestion)
    {
        $materialQuestion->delete();

        return response()->json();
    }
}
