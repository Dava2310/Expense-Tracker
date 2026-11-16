<?php

namespace App\Http\Requests;

use App\Rules\UniqueCategoryName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        abort_unless($this->route('category')->user_id === $this->user()->id, 404);

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
