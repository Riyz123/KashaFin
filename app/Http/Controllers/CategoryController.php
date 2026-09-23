<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category = Category::create([
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'is_default' => false,
        ]);

        if ($request->wantsJson()) {
            return response()->json($category);
        }

        return back()->with('status', 'Categoría creada correctamente.');
    }

    public function destroy(Request $request, Category $category)
    {
        abort_unless($category->user_id === $request->user()->id, 403);

        $category->delete();

        return back()->with('status', 'Categoría eliminada correctamente.');
    }
}
