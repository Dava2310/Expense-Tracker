@props(['name'])

@php
    $paths = match ($name) {
        'dashboard' => '<rect x="3" y="3" width="7.5" height="7.5" rx="2"></rect><rect x="13.5" y="3" width="7.5" height="7.5" rx="2"></rect><rect x="3" y="13.5" width="7.5" height="7.5" rx="2"></rect><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="2"></rect>',
        'accounts' => '<rect x="3" y="5.5" width="18" height="14" rx="2.5"></rect><path d="M3 10h18"></path><path d="M16.5 15h1.5"></path>',
        'expenses' => '<path d="M7 17 17 7"></path><path d="M8 7h9v9"></path>',
        'incomes' => '<path d="M17 7 7 17"></path><path d="M16 17H7V8"></path>',
        'categories' => '<path d="M3 4.5v6.1c0 .5.2 1 .6 1.4l8.4 8.4a2 2 0 0 0 2.8 0l5.7-5.7a2 2 0 0 0 0-2.8L12 3.6c-.4-.4-.9-.6-1.4-.6H4.5C3.7 3 3 3.7 3 4.5Z"></path><circle cx="7.5" cy="7.5" r="1.25"></circle>',
        'currencies' => '<circle cx="12" cy="12" r="9"></circle><path d="M14.8 9.2c-.5-.8-1.5-1.2-2.8-1.2-1.6 0-2.7.8-2.7 2s1 1.7 2.7 2 2.7.8 2.7 2-1.1 2-2.7 2c-1.3 0-2.3-.4-2.8-1.2"></path><path d="M12 6.5V8m0 8v1.5"></path>',
        'user' => '<circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="m16 17 5-5-5-5"></path><path d="M21 12H9"></path>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"></path>',
    };
@endphp

<svg {{ $attributes->merge(['width' => 17, 'height' => 17]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths !!}</svg>
