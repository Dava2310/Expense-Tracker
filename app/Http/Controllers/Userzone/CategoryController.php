<?php

namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
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
}
