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

    foreach (Navigation::menuItems(authenticated: true) as $item) {
        $response->assertSee('href="'.route($item['route']).'"', false)->assertSee($item['label']);
    }
});

test('the footer only shows the copyright and the creator, without a menu', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertSee('© '.now()->year.' Saldo')
        ->assertSee('Created by Daniel Vetencourt')
        ->assertDontSee('aria-label="Footer"', false);
});

test('the landing page footer shows the copyright and the creator, without a menu', function () {
    $this->get(route('welcome'))
        ->assertOk()
        ->assertSee('© '.now()->year.' Saldo')
        ->assertSee('Created by Daniel Vetencourt')
        ->assertDontSee('aria-label="Footer"', false);
});
