<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Products;
use App\Traits\HasIndexFilters;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    use HasIndexFilters;
    /**
     * Display the category management page.
     */
    public function index(Request $request)
    {
        $query = Category::withCount('products');

        $this->applySearch($query, $request->input('search'), ['id', 'name']);

        $this->applySort($query, $request, ['id', 'name', 'created_at', 'updated_at'], 'name', 'asc');

        $categories = $query->get()->map(fn ($cat) => [
            'id' => $cat->id,
            'name' => $cat->name,
            'products_count' => $cat->products_count,
            'created_at' => $cat->created_at?->format('M d, Y h:i A'),
            'updated_at' => $cat->updated_at?->format('M d, Y h:i A'),
        ]);

        // If the request wants JSON (e.g. from Products filter dropdown), return JSON
        if ($request->wantsJson()) {
            return response()->json(['categories' => $categories]);
        }

        $search = $request->input('search', '');
        $sortField = $request->input('sort', 'name');
        $sortDir = $request->input('direction', 'asc');

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
            'filters' => [
                'search' => $search ?? '',
                'sort' => $sortField,
                'direction' => $sortDir,
            ],
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string|max:10|regex:/^[A-Z]+$/|unique:categories,id',
            'name' => 'required|string|max:255|unique:categories,name',
        ], [
            'id.regex' => 'Category ID must contain only uppercase letters.',
            'id.unique' => 'This Category ID already exists.',
            'name.unique' => 'This Category Name already exists.',
        ]);

        Category::create($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Update an existing category.
     */
    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'id' => 'required|string|max:10|regex:/^[A-Z]+$/|unique:categories,id,' . $category->id . ',id',
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id . ',id',
        ], [
            'id.regex' => 'Category ID must contain only uppercase letters.',
        ]);

        $newId = $validated['id'];
        $oldId = $category->id;

        // If ID is changing, check if products reference this category
        if ($newId !== $oldId) {
            $productCount = Products::where('category_id', $oldId)->count();
            if ($productCount > 0) {
                return back()->withErrors([
                    'id' => "Cannot change Category ID because {$productCount} product(s) are using it.",
                ]);
            }

            // Delete old, create new since PK is changing
            $category->delete();
            Category::create($validated);
        } else {
            $category->update(['name' => $validated['name']]);
        }

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Delete a category.
     */
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);

        // Check for associated products (including soft-deleted)
        $productCount = Products::withTrashed()->where('category_id', $id)->count();
        if ($productCount > 0) {
            return back()->withErrors([
                'delete' => "Cannot delete this category because {$productCount} product(s) are associated with it.",
            ]);
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
