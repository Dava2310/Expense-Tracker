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
     * @var list<array{label: string, route: string, pattern: string, icon: string}>
     */
    public array $items;

    /**
     * The single definition of the application menu.
     *
     * @return list<array{label: string, route: string, pattern: string, icon: string}>
     */
    public static function menuItems(): array
    {
        return [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'pattern' => 'dashboard', 'icon' => 'dashboard'],
            ['label' => 'Accounts', 'route' => 'accounts.index', 'pattern' => 'accounts.*', 'icon' => 'accounts'],
            ['label' => 'Expenses', 'route' => 'expenses.index', 'pattern' => 'expenses.*', 'icon' => 'expenses'],
            ['label' => 'Incomes', 'route' => 'incomes.index', 'pattern' => 'incomes.*', 'icon' => 'incomes'],
            ['label' => 'Categories', 'route' => 'categories.index', 'pattern' => 'categories.*', 'icon' => 'categories'],
            ['label' => 'Currencies', 'route' => 'currencies.index', 'pattern' => 'currencies.*', 'icon' => 'currencies'],
        ];
    }

    /**
     * Create a new component instance.
     */
    public function __construct(Request $request)
    {
        $this->user = $request->user();
        $this->items = self::menuItems();

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
