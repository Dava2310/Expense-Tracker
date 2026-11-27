<?php

namespace App\Http\Requests;

use App\Rules\UniqueCategoryName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the form that creates a category.
 */
class StoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Rules for the name (unique per user) and the details.
     * `bail` skips the uniqueness query when the name is already invalid.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'max:255', new UniqueCategoryName($this->user())],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Plain-words names of the fields, used in the messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'category name',
            'description' => 'details',
        ];
    }
}
