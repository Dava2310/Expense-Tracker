<?php

namespace App\Http\Requests;

use App\Rules\UniqueCategoryName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
     * Get the validation rules that apply to the request.
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
     * The plain-words names of the fields, used by the messages of both the create and the edit form.
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

    /**
     * Messages written for people, shared with the edit form (UpdateCategoryRequest extends this class).
     * The message for a repeated name is written in the UniqueCategoryName rule.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter the category name.',
            'name.string' => 'The :attribute must be text.',
            'name.max' => 'The :attribute can have at most :max characters.',

            'description.string' => 'The :attribute must be text.',
            'description.max' => 'The :attribute can have at most :max characters.',
        ];
    }
}
