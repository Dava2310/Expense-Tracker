<?php

namespace App\Http\Requests;

use App\Rules\UniqueCategoryName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Who can update a category is decided by the category policy, which the controller asks.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'max:255', new UniqueCategoryName($this->user(), $this->route('category'))],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
