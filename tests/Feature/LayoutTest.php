<?php

use App\Models\User;
use App\View\Components\Layout\Navigation;

test('the app layout puts the page title in the document title', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('accounts.index'))
        ->assertSee('<title>Accounts — ', false);
});

test('the header renders every item of the menu definition', function () {
    $response = $this->actingAs(User::factory()->create())->get(route('dashboard'));

    foreach (Navigation::menuItems() as $item) {
        $response->assertSee('href="'.route($item['route']).'"', false)->assertSee($item['label']);
    }
});
