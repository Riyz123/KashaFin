<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::query()
            ->whereNull('user_id')
            ->orderBy('name')
            ->get();

        return view('admin.categories.index', ['categories' => $categories]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
        ]);

        Category::create([
            'user_id' => null,
            'name' => $data['name'],
            'is_default' => true,
            'is_active' => true,
        ]);

        return back()->with('status', 'Categoría global creada correctamente.');
    }

    public function update(Request $request, Category $category)
    {
        abort_if($category->user_id !== null, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name,'.$category->id],
        ]);

        $category->update($data);

        return back()->with('status', 'Categoría actualizada correctamente.');
    }

    public function toggleActive(Category $category)
    {
        abort_if($category->user_id !== null, 403);

        $category->is_active = ! $category->is_active;
        $category->save();

        return back()->with('status', $category->is_active ? 'Categoría activada correctamente.' : 'Categoría desactivada correctamente.');
    }
}
