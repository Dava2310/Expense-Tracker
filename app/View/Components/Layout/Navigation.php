<?php

namespace App\View\Components\Layout;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\Component;
use Illuminate\View\View;

class Navigation extends Component
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
     * The menu entries rendered by the view.
     *
     * @var list<array{label: string, route: string, pattern: string, icon: string|null}>
     */
    public array $items;

    /**
     * The single definition of the application menu, shared by the header and the footer.
     *
     * Items are tagged with who sees them: `auth` for signed-in users, `guest` for visitors.
     *
     * @return list<array{label: string, route: string, pattern: string, icon: string|null}>
     */
    public static function menuItems(?bool $authenticated = null): array
    {
        $authenticated ??= auth()->check();

        $items = [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'pattern' => 'dashboard', 'icon' => 'dashboard', 'audience' => 'auth'],
            ['label' => 'Accounts', 'route' => 'accounts.index', 'pattern' => 'accounts.*', 'icon' => 'accounts', 'audience' => 'auth'],
            ['label' => 'Expenses', 'route' => 'expenses.index', 'pattern' => 'expenses.*', 'icon' => 'expenses', 'audience' => 'auth'],
            ['label' => 'Incomes', 'route' => 'incomes.index', 'pattern' => 'incomes.*', 'icon' => 'incomes', 'audience' => 'auth'],
            ['label' => 'Categories', 'route' => 'categories.index', 'pattern' => 'categories.*', 'icon' => 'categories', 'audience' => 'auth'],
            ['label' => 'Currencies', 'route' => 'currencies.index', 'pattern' => 'currencies.*', 'icon' => 'currencies', 'audience' => 'auth'],
            ['label' => 'Home', 'route' => 'welcome', 'pattern' => 'welcome', 'icon' => null, 'audience' => 'guest'],
            ['label' => 'Log in', 'route' => 'login', 'pattern' => 'login', 'icon' => null, 'audience' => 'guest'],
            ['label' => 'Get started', 'route' => 'register', 'pattern' => 'register', 'icon' => null, 'audience' => 'guest'],
        ];

        $audience = $authenticated ? 'auth' : 'guest';

        return array_values(array_filter(
            $items,
            fn (array $item): bool => $item['audience'] === $audience,
        ));
    }

    /**
     * Create a new component instance.
     */
    public function __construct(Request $request)
    {
        $this->user = $request->user();
        $this->items = self::menuItems(authenticated: true);

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
        return view('components.layout.navigation');
    }
}
