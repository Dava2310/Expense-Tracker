<?php

namespace App\Rules;

use App\Models\Category;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class UniqueCategoryName implements ValidationRule
{
    /**
     * @param  Category|null  $ignoring  The category being edited, which can keep its own name
     */
    public function __construct(private User $user, private ?Category $ignoring = null) {}

    /**
     * A user cannot have two categories with the same name, whatever the letter case.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $alreadyExists = $this->user->categories()
            ->whereRaw('lower(name) = ?', [mb_strtolower($value)])
            ->when($this->ignoring, fn ($query) => $query->whereKeyNot($this->ignoring))
            ->exists();

        if ($alreadyExists) {
            $fail('You already have a category called '.$value.'.');
        }
    }
}
