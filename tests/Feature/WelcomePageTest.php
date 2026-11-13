<?php

use App\Models\User;

test('guests see the landing page with links to register and log in', function () {
    $this->get(route('welcome'))
        ->assertOk()
        ->assertSee('Your money, in plain sight.')
        ->assertSee('Create your account')
        ->assertSee(route('register'), false)
        ->assertSee(route('login'), false);
});

test('authenticated users are pointed to the dashboard instead of registration', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('welcome'))
        ->assertOk()
        ->assertSee(route('dashboard'), false)
        ->assertDontSee('Create your account')
        ->assertDontSee(route('register'), false);
});
