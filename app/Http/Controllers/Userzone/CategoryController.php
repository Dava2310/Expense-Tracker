<?php

namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Handles listing, creating, editing and deleting the user's categories.
 */
class CategoryController extends Controller
{
    /**
     * Lists the categories of the logged-in user with the number of incomes and expenses using each one
     */
    public function index(Request $request): View
    {
        return view('userzone.categories.index', [
            'categories' => $request->user()->categories()
                ->select('id', 'name', 'description')
                ->withCount(['incomes', 'expenses'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Renders the form to create a new category
     */
    public function create(): View
    {
        Gate::authorize('create', Category::class);

        return view('userzone.categories.create');
    }

    /**
     * Handles the creation of the new Category
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Gate::authorize('create', Category::class);

        $request->user()->categories()->create($request->validated());

        return redirect()->route('categories.index')->with('status', 'category-created');
    }

    /**
     * Renders the form to edit one of the user's categories
     */
    public function edit(Category $category): View
    {
        Gate::authorize('update', $category);

        return view('userzone.categories.edit', [
            'category' => $category->loadCount(['incomes', 'expenses']),
        ]);
    }

    /**
     * Handles the update of a category
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        Gate::authorize('update', $category);

        $category->update($request->validated());

        return redirect()->route('categories.index')->with('status', 'category-updated');
    }

    /**
     * Deletes a category unless an income or an expense uses it.
     */
    public function destroy(Category $category): RedirectResponse
    {
        Gate::authorize('delete', $category);

        // Checked again in case the category was used since the page loaded
        if ($category->isInUse()) {
            return redirect()->route('categories.index')
                ->withErrors(['category' => $category->name.' is in use, so it can’t be deleted.']);
        }

        $category->delete();

        return redirect()->route('categories.index')->with('status', 'category-deleted');
    }
}
