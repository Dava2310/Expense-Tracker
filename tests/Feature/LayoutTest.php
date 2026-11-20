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

test('the footer shows the same menu items as the header for signed-in users', function () {
    $response = $this->actingAs(User::factory()->create())->get(route('dashboard'));

    foreach (Navigation::menuItems(authenticated: true) as $item) {
        $response->assertSeeInOrder(['aria-label="Main"', $item['label'], 'aria-label="Footer"', $item['label']], false);
    }
});

test('guests see the public menu items in the footer of the landing page', function () {
    $response = $this->get(route('welcome'));

    foreach (Navigation::menuItems(authenticated: false) as $item) {
        $response->assertSee('href="'.route($item['route']).'"', false)->assertSee($item['label']);
    }
});
