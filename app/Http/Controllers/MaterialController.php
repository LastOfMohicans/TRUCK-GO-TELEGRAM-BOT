<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller
{

    public function test(): string
    {
        return "fdsniojf2";
    }

    public function index()
    {
        return Material::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required'],
        ]);

        return Material::create($request->validated());
    }

    public function show(Material $material)
    {
        return $material;
    }

    public function update(Request $request, Material $material)
    {
        $request->validate([
            'name' => ['required'],
        ]);

        $material->update($request->validated());

        return $material;
    }

    public function destroy(Material $material)
    {
        $material->delete();

        return response()->json();
    }
}
