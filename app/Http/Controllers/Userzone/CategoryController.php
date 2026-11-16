<?php

namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /***
     * Lists the categories of the logged-in user with the number of incomes and expenses using each one
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        return view('userzone.categories.index', [
            'categories' => $request->user()->categories()->withCount(['incomes', 'expenses'])->orderBy('name')->get(),
        ]);
    }

    /***
     * Renders the form to create a new category
     *
     * @return View
     */
    public function create(): View
    {
        return view('userzone.categories.create');
    }

    /***
     * Handles the creation of the new Category
     *
     * @param StoreCategoryRequest $request
     * @return RedirectResponse
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $request->user()->categories()->create($request->validated());

        return redirect()->route('categories.index')->with('status', 'category-created');
    }

    /***
     * Renders the form to edit one of the user's categories
     *
     * @param Request $request
     * @param Category $category
     * @return View
     */
    public function edit(Request $request, Category $category): View
    {
        $this->ensureOwnedBy($request, $category);

        return view('userzone.categories.edit', [
            'category' => $category->loadCount(['incomes', 'expenses']),
        ]);
    }

    /***
     * Handles the update of a category
     *
     * @param UpdateCategoryRequest $request
     * @param Category $category
     * @return RedirectResponse
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('categories.index')->with('status', 'category-updated');
    }

    /***
     * Deletes one of the user's categories, unless an income or an expense uses it
     *
     * @param Request $request
     * @param Category $category
     * @return RedirectResponse
     */
    public function destroy(Request $request, Category $category): RedirectResponse
    {
        $this->ensureOwnedBy($request, $category);

        // The page already hides the option, but the category may have been used since it was loaded
        if ($category->isInUse()) {
            return redirect()->route('categories.index')
                ->withErrors(['category' => $category->name.' is in use, so it can’t be deleted.']);
        }

        $category->delete();

        return redirect()->route('categories.index')->with('status', 'category-deleted');
    }

    /**
     * Categories that belong to somebody else are reported as not found.
     */
    private function ensureOwnedBy(Request $request, Category $category): void
    {
        abort_unless($category->user_id === $request->user()->id, 404);
    }
}
