<?php

use App\Models\User;

test('login page links to the forgot password page', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Welcome back')
        ->assertSee('Forgot password?')
        ->assertSee(route('password.request'), false);
});

test('forgot password page offers a way back to log in', function () {
    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('Forgot your password?')
        ->assertSee('Send reset link')
        ->assertSee(route('login'), false);
});

test('register page asks for name, email, password and confirmation', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Create your account')
        ->assertSee('name="name"', false)
        ->assertSee('name="email"', false)
        ->assertSee('name="password"', false)
        ->assertSee('name="password_confirmation"', false);
});

test('registering with a taken email shows a friendly message and a login link', function () {
    $user = User::factory()->create();

    $this->from(route('register'))
        ->post(route('register'), [
            'name' => 'Someone Else',
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('register'));

    $this->get(route('register'))
        ->assertSee('An account with this email already exists.')
        ->assertSee('Log in instead');
});

test('reset password page uses the Saldo layout and prefills the email', function () {
    $this->get(route('password.reset', ['token' => 'fake-token', 'email' => 'alex@example.com']))
        ->assertOk()
        ->assertSee('Choose a new password')
        ->assertSee('value="alex@example.com"', false)
        ->assertSee('name="token"', false)
        ->assertSee('Reset password');
});
