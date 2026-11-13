<?php

namespace App\View\Components\Saldo;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\Component;
use Illuminate\View\View;

class TopNav extends Component
{
    /**
     * The authenticated user shown in the user menu.
     */
    public User $user;

    /**
     * The user's initials, shown in the avatar (up to two letters).
     */
    public string $initials;

    /**
     * SVG attributes shared by every menu icon.
     */
    public string $iconAttributes = 'width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';

    /**
     * Create a new component instance.
     */
    public function __construct(Request $request)
    {
        $this->user = $request->user();

        $this->initials = str($this->user->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.saldo.top-nav');
    }
}
