<?php

namespace App\View\Components\Layout;

use Illuminate\View\Component;
use Illuminate\View\View;

class Footer extends Component
{
    /**
     * The menu entries shown in the footer, taken from the same definition as the header.
     *
     * @var list<array{label: string, route: string, pattern: string, icon: string|null}>
     */
    public array $items;

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $this->items = Navigation::menuItems();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.layout.footer');
    }
}
