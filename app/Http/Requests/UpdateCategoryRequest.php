<?php

namespace App\Http\Requests;

use App\Rules\UniqueCategoryName;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates the form that edits a category.
 */
class UpdateCategoryRequest extends StoreCategoryRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
