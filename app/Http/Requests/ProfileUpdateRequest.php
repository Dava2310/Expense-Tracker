<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'photo' => ['nullable', 'image', 'mimes:jpg,png,webp', 'max:2048'],
        ];
    }

    /**
     * Plain-language messages for the photo field.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.uploaded' => 'The photo could not be uploaded. It may be larger than the server allows.',
            'photo.image' => 'Choose a JPG, PNG or WebP picture.',
            'photo.mimes' => 'Choose a JPG, PNG or WebP picture.',
            'photo.max' => 'The photo must be 2 MB or smaller.',
        ];
    }
}
