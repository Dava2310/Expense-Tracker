<?php

namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
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
}
