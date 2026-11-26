<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

test('a user signs in with the right password and is sent to the dashboard', function () {
    $user = User::factory()->create();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('a user cannot sign in with a wrong password', function () {
    $user = User::factory()->create();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('a signed-in user signs out', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('logout'))
        ->assertRedirect('/');

    $this->assertGuest();
});

test('a new user registers, is signed in and receives the starter categories and currencies', function () {
    $this->post(route('register'), ['name' => 'New User', 'email' => 'new@example.com', 'password' => 'password', 'password_confirmation' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));

    $user = User::firstWhere('email', 'new@example.com');

    $this->assertAuthenticatedAs($user);
    expect($user->categories()->count())->toBe(11)
        ->and($user->currencies()->pluck('symbol')->all())->toBe(['$', '€']);
});

test('registering with an email that is already taken explains it and creates nothing', function () {
    $existing = User::factory()->create();

    $this->post(route('register'), ['name' => 'Someone Else', 'email' => $existing->email, 'password' => 'password', 'password_confirmation' => 'password'])
        ->assertSessionHasErrors(['email' => 'An account with this email already exists.']);

    expect(User::count())->toBe(1);
    $this->assertGuest();
});

test('a user resets a forgotten password with the emailed link', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $this->post(route('password.store'), ['token' => $notification->token, 'email' => $user->email, 'password' => 'brand-new-password', 'password_confirmation' => 'brand-new-password'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        return true;
    });

    $this->post(route('login'), ['email' => $user->email, 'password' => 'brand-new-password']);

    $this->assertAuthenticatedAs($user);
});

test('guests see the landing page with links to register and sign in', function () {
    $this->get(route('welcome'))
        ->assertOk()
        ->assertSee(route('register'), false)
        ->assertSee(route('login'), false);
});

test('signed-in users are pointed to the dashboard instead of registration', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('welcome'))
        ->assertOk()
        ->assertSee(route('dashboard'), false)
        ->assertDontSee(route('register'), false);
});
