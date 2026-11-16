<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class UniqueCategoryName implements ValidationRule
{
    public function __construct(private User $user) {}

    /**
     * A user cannot have two categories with the same name, whatever the letter case.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $alreadyExists = $this->user->categories()
            ->whereRaw('lower(name) = ?', [mb_strtolower($value)])
            ->exists();

        if ($alreadyExists) {
            $fail('You already have a category called '.$value.'.');
        }
    }
}
