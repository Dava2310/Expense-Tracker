<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

test('a user updates their name and email, and the new email is no longer verified', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => 'New Name', 'email' => 'new@example.com'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('New Name')
        ->and($user->email)->toBe('new@example.com')
        ->and($user->email_verified_at)->toBeNull();
});

test('saving the profile with the same email keeps it verified', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => 'New Name', 'email' => $user->email])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('a user changes their password after typing the current one', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->put(route('password.update'), ['current_password' => 'password', 'password' => 'new-password', 'password_confirmation' => 'new-password'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('a wrong current password does not change the password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->put(route('password.update'), ['current_password' => 'wrong-password', 'password' => 'new-password', 'password_confirmation' => 'new-password'])
        ->assertSessionHasErrorsIn('updatePassword', 'current_password');

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

test('a user uploads a profile photo and removes it', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => $user->name, 'email' => $user->email, 'photo' => UploadedFile::fake()->image('me.png', 400, 300)])
        ->assertSessionHasNoErrors();

    $media = $user->refresh()->getFirstMedia('avatar');
    Storage::disk('public')->assertExists($media->getPathRelativeToRoot());

    $this->delete(route('profile.photo.destroy'))->assertRedirect(route('profile.edit'));

    expect($user->refresh()->getFirstMedia('avatar'))->toBeNull();
    Storage::disk('public')->assertMissing($media->getPathRelativeToRoot());
});

test('a file that is not a picture is refused as a profile photo', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => $user->name, 'email' => $user->email, 'photo' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')])
        ->assertSessionHasErrors(['photo' => 'Choose a JPG, PNG or WebP picture.']);

    expect($user->refresh()->getFirstMedia('avatar'))->toBeNull();
});

test('deleting an account removes the user and everything they own, and nothing of other users', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $user->createStarterCategories();
    $user->setProfilePhoto(UploadedFile::fake()->image('me.png', 300, 300));
    $currency = Currency::factory()->for($user)->create();
    $checking = Account::factory()->for($user)->for($currency)->create();
    $savings = Account::factory()->for($user)->for($currency)->create();
    Account::factory()->for($user)->for($currency)->inactive()->create();
    $category = $user->categories()->first();
    Income::factory()->for($checking)->for($category)->create();
    Expense::factory()->for($checking)->for($category)->create();
    Transfer::factory()->create(['from_account_id' => $checking->id, 'to_account_id' => $savings->id]);
    $strangersAccount = Account::factory()->for(User::factory())->create();

    $this->actingAs($user)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect('/');

    $this->assertGuest();
    expect(User::find($user->id))->toBeNull()
        ->and(Account::withTrashed()->pluck('id')->all())->toBe([$strangersAccount->id])
        ->and(Currency::where('user_id', $user->id)->count())->toBe(0)
        ->and(Category::where('user_id', $user->id)->count())->toBe(0)
        ->and(Income::count() + Expense::count() + Transfer::count())->toBe(0)
        ->and(DB::table('media')->count())->toBe(0);
});

test('a wrong password does not delete the account', function () {
    $user = User::factory()->create();
    $user->createStarterCategories();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), ['password' => 'wrong-password'])
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect(route('profile.edit'));

    $this->assertModelExists($user);
    expect($user->categories()->count())->toBe(11);
});
