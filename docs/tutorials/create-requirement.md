# How to build a CREATE / POST requirement

A step-by-step guide to the "create a record" flow in this project, using **Account creation** as the worked example. To build another resource (Expense, Income, Category...) follow the same steps and replace `Account` / `accounts` with your own names.

## The flow

```
Browser                       Laravel
-------                       -------
GET  /accounts/create   -->   Route --> Controller@create --> View (form)
                                                                  |
POST /accounts          -->   Route --> Form Request (validation) |
                                              |                   |
                                   fails: back to the form <------+  (errors + old input)
                                              |
                                          passes
                                              v
                                   Controller@store --> Model::create() --> database
                                              |
                                        redirect + flash message
```

Two routes, two controller methods, one Form Request, one view.

## The order to build it in

Build things in dependency order, so each step has what it needs when you write it:

| # | Step | File |
|---|------|------|
| 0 | Schema, model, relations, factory | `database/migrations`, `app/Models` |
| 1 | Create the controller | `app/Http/Controllers/Userzone/` |
| 2 | Add the routes | `routes/web.php` |
| 3 | Create the Form Request (validation) | `app/Http/Requests/` |
| 4 | Set `$fillable` on the model | `app/Models/` |
| 5 | Write the controller methods | `app/Http/Controllers/Userzone/` |
| 6 | Build the view (form) | `resources/views/userzone/` |
| 7 | Check it by hand | browser |
| 8 | Write feature tests | `tests/Feature/` |
| 9 | Format and verify | Pint, `route:list`, tests |

---

## Step 0: Prerequisites

Before touching controllers, the resource itself must exist.

- **Migration** with the columns. The column types and `NOT NULL` decide the validation rules in step 3.
- **Model** with its relationships, for example `User::accounts()` (`hasMany`) and `Account::user()` (`belongsTo`).
- **Factory** (used by seeders and tests).

Create them with Artisan, never by hand:

```bash
php artisan make:model Account -mf --no-interaction
```

Our `accounts` table:

```php
Schema::create('accounts', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('bank');
    $table->string('details');           // NOT NULL
    $table->decimal('balance');          // decimal(8,2): max 999999.99
    $table->decimal('min_balance');
    $table->foreignId('user_id')->constrained();
    $table->timestamps();
});
```

Relation on the owner, needed in step 6:

```php
// app/Models/User.php
public function accounts(): HasMany
{
    return $this->hasMany(Account::class);
}
```

> If a migration references another table (a foreign key), that table's migration must run **first**. Migrations run in filename order, so the timestamp in the name matters.

---

## Step 1: Create the controller

A route points at a class, and `php artisan route:list` fails if the class does not exist. So create the controller **before** the routes.

```bash
php artisan make:controller Userzone/AccountController --no-interaction
```

This creates `app/Http/Controllers/Userzone/AccountController.php`, next to `ProfileController` and `DashboardController`. Controllers for logged-in pages go in the `Userzone` folder.

Add the two empty methods the routes will call. The bodies come in step 5:

```php
namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function create(): View
    {
        //
    }

    public function store(): RedirectResponse
    {
        //
    }
}
```

---

## Step 2: Add the routes

In `routes/web.php`, inside the `auth` group, so only logged-in users can reach them:

```php
use App\Http\Controllers\Userzone\AccountController;

Route::middleware('auth')->group(function () {
    // Account routes
    Route::get('/accounts/create', [AccountController::class, 'create'])->name('accounts.create');
    Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
});
```

- `GET /accounts/create` shows the empty form. `POST /accounts` receives it.
- **Always name the routes** (`accounts.create`, `accounts.store`) and use `route('accounts.store')` everywhere. Never hardcode URLs.
- Keep `GET /accounts` free. It is the conventional path for the **list** page (`index`).
- Shorter equivalent: `Route::resource('accounts', AccountController::class)->only(['create', 'store']);`

Check:

```bash
php artisan route:list --name=accounts
```

Expected: `GET accounts/create` and `POST accounts`. Visiting `/accounts/create` while logged out redirects to `/login`.

---

## Step 3: Create the Form Request (validation)

A Form Request validates the data **before** the controller runs. If validation fails, Laravel redirects back with the errors and the typed values, and the controller never runs.

```bash
php artisan make:request StoreAccountRequest --no-interaction
```

```php
namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'bank' => ['required', 'string', 'max:255'],
            'details' => ['required', 'string', 'max:255'],
            'balance' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:999999.99'],
            'min_balance' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:999999.99'],
        ];
    }
}
```

**`authorize()`** must return `true`, because the default is `false` and blocks everyone with a 403. The `auth` middleware already requires login.

**Derive the rules from the migration:**

| Column | Rule |
|---|---|
| `string()` | `string`, `max:255` |
| `NOT NULL` | `required` (use `nullable` only if the column is `->nullable()`) |
| `decimal(8,2)` | `numeric`, `decimal:0,2`, `max:999999.99` |
| money / quantity | `min:0` |

Without `max`, an over-long value fails at the database with an ugly error instead of a friendly message.

**Do not add the owner (`user_id`) to the rules.** The controller sets it from the logged-in user (step 5). Anything without a rule is dropped by `validated()`.

Optional: add `gte:min_balance` to `balance` if an account must not start below its minimum.

---

## Step 4: Set `$fillable` on the model

`create()` and `fill()` only set attributes listed in `$fillable`. This protects against mass assignment (a user posting extra fields such as `user_id`).

```php
// app/Models/Account.php
protected $fillable = [
    'name',
    'bank',
    'details',
    'balance',
    'min_balance',
];
```

- List the same fields as the Form Request rules.
- **Leave out ownership columns** such as `user_id`. The relationship call in step 5 fills them in.
- Without `$fillable`, the values are ignored (or an exception is thrown in strict mode) and the insert fails on `NOT NULL` columns.

---

## Step 5: Write the controller methods

```php
use App\Http\Requests\StoreAccountRequest;

public function create(): View
{
    return view('userzone.accounts.create');
}

public function store(StoreAccountRequest $request): RedirectResponse
{
    $request->user()->accounts()->create($request->validated());

    return redirect()->route('dashboard')->with('status', 'account-created');
}
```

- **Type-hint `StoreAccountRequest`** in `store()`. That is what makes Laravel run the validation first.
- **`$request->user()->accounts()->create(...)`** creates the account and sets `user_id` to the logged-in user. Nobody can create a record for someone else, because `user_id` never comes from the form.
- **`$request->validated()`** returns only the fields that have a rule.
- **Always redirect after a successful POST.** Otherwise refreshing the page submits the form again and creates a duplicate.
- **`->with('status', '...')`** stores a one-time flash message for the next page. Showing it is up to that page's view.
- `view('userzone.accounts.create')` maps to `resources/views/userzone/accounts/create.blade.php`. Dots are folder separators.
- Keep controllers thin. If the logic grows, move it to a dedicated class.

---

## Step 6: Build the view (form)

```bash
php artisan make:view userzone.accounts.create --no-interaction
```

`resources/views/userzone/accounts/create.blade.php`:

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Create Account
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('accounts.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-breeze.input-label for="name" value="Name"/>
                        <x-breeze.text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus/>
                        <x-breeze.input-error class="mt-2" :messages="$errors->get('name')"/>
                    </div>

                    {{-- bank, details: same block, different name/id/label --}}

                    <div>
                        <x-breeze.input-label for="balance" value="Balance"/>
                        <x-breeze.text-input id="balance" name="balance" type="number" step="0.01" min="0" max="999999.99" class="mt-1 block w-full" :value="old('balance')" required/>
                        <x-breeze.input-error class="mt-2" :messages="$errors->get('balance')"/>
                    </div>

                    {{-- min_balance: same as balance --}}

                    <div class="flex items-center gap-4">
                        <x-breeze.primary-button>Create Account</x-breeze.primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
```

Rules for every form:

- **`@csrf` is mandatory.** Without it the POST fails with `419 Page Expired`.
- `method="POST"` with `action="{{ route('accounts.store') }}"`.
- **Each input's `name` must match the rule key** in the Form Request.
- **`:value="old('name')"`** refills the field after a validation error. The colon makes Blade evaluate PHP.
- **`<x-breeze.input-error :messages="$errors->get('name')">`** prints that field's errors, and nothing if there are none.
- For decimals use `type="number"` with `step="0.01"`; otherwise browsers only accept whole numbers.
- Browser attributes (`required`, `min`, `max`) are a convenience. The real validation is the Form Request.
- Reuse the existing `x-breeze.*` components. Do not write new ones for this.

---

## Step 7: Check it by hand

1. Seed a user: `php artisan migrate:fresh --seed` (login `test@example.com` / `password`).
2. Run the app: `composer run dev`.
3. Open `/accounts/create`.
4. Submit an empty form, then bad values (`-5`, `10.555`), then a valid one.
5. Valid submit: you land on the dashboard and a new row exists with your `user_id`.

If a view change does not show up: `php artisan view:clear`. If styles are missing: `npm run build`, or `npm run dev`.

---

## Step 8: Write feature tests

Create the file with Pest:

```bash
php artisan make:test --pest --no-interaction AccountCreationTest
```

Test the form, the happy path, the failure paths and the security rules:

```php
<?php

use App\Models\Account;
use App\Models\User;

test('create form is displayed to a logged in user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('accounts.create'))
        ->assertOk();
});

test('guests are redirected to login', function () {
    $this->get(route('accounts.create'))->assertRedirect(route('login'));
    $this->post(route('accounts.store'), [])->assertRedirect(route('login'));
});

test('valid data creates an account owned by the user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('accounts.store'), [
            'name' => 'Main account',
            'bank' => 'Test Bank',
            'details' => 'Salary account',
            'balance' => '100.50',
            'min_balance' => '10',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', 'account-created');

    $this->assertDatabaseHas('accounts', [
        'user_id' => $user->id,
        'name' => 'Main account',
    ]);
});

test('invalid data is rejected and nothing is created', function (string $field, mixed $value) {
    $user = User::factory()->create();
    $valid = [
        'name' => 'Main account',
        'bank' => 'Test Bank',
        'details' => 'Salary account',
        'balance' => '100.50',
        'min_balance' => '10',
    ];

    $this->actingAs($user)
        ->post(route('accounts.store'), [...$valid, $field => $value])
        ->assertSessionHasErrors($field);

    expect(Account::count())->toBe(0);
})->with([
    'missing name' => ['name', ''],
    'name too long' => ['name', str_repeat('a', 256)],
    'negative balance' => ['balance', '-5'],
    'too many decimals' => ['balance', '10.555'],
    'balance too large' => ['balance', '1000000'],
    'balance not a number' => ['min_balance', 'abc'],
]);

test('a submitted user_id is ignored', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user)->post(route('accounts.store'), [
        'name' => 'Main account',
        'bank' => 'Test Bank',
        'details' => 'Salary account',
        'balance' => '100.50',
        'min_balance' => '10',
        'user_id' => $other->id,
    ]);

    $this->assertDatabaseHas('accounts', ['name' => 'Main account', 'user_id' => $user->id]);
});
```

Test-writing rules for this project:

- Each test builds its own data with factories (`User::factory()->create()`). The `RefreshDatabase` trait is already applied to `tests/Feature`.
- Use named assertions (`assertOk()`, `assertRedirect()`), not `assertStatus(...)`.
- Put cases that differ only by input into a `->with([...])` dataset, and give each case a name.
- On the failure path, assert that **nothing was created**.

Run only these tests:

```bash
php artisan test --compact tests/Feature/AccountCreationTest.php
```

---

## Step 9: Format and verify

```bash
vendor/bin/pint --dirty --format agent      # code style, required after editing PHP
php artisan route:list --name=accounts      # routes and middleware are what you expect
php artisan test --compact                  # whole suite is green
```

Then commit in small logical groups with Conventional Commit messages (for example `feat(accounts): ...`) and open a PR. CI runs the build, migrations, seeders and tests on SQLite and MySQL.

---

## Checklist

- [ ] Migration, model, relations, factory exist (step 0)
- [ ] Controller created **before** the routes (step 1)
- [ ] `GET .../create` and `POST ...` routes named, inside the `auth` group (step 2)
- [ ] Form Request: `authorize()` returns `true`, rules derived from the migration (step 3)
- [ ] `$fillable` set, ownership column left out (step 4)
- [ ] `store()` type-hints the Form Request, uses `$request->user()->relation()->create(...)`, redirects (step 5)
- [ ] View has `@csrf`, `old()`, and an error block per field (step 6)
- [ ] Tried by hand, including invalid input (step 7)
- [ ] Feature tests cover: form, success, validation, guests, ignored `user_id` (step 8)
- [ ] Pint, `route:list`, full test suite pass (step 9)

## Common errors

| Symptom | Cause | Fix |
|---|---|---|
| `419 Page Expired` on submit | missing `@csrf` | add `@csrf` inside the form |
| `View [...] not found` | wrong view name or file path | dots are folders: `userzone.accounts.create` |
| `Target class [...] does not exist` in `route:list` | controller not created or not imported | create it (step 1) and add the `use` import |
| 403 on submit | `authorize()` returns `false` | return `true` |
| Insert fails on `NOT NULL` column | field missing from `$fillable` | add it to `$fillable` (step 4) |
| Account created for the wrong user | `user_id` taken from the request | use `$request->user()->accounts()->create(...)` |
| Form data lost after an error | missing `old()` | `:value="old('field')"` |
| Duplicate records after refresh | no redirect after POST | return `redirect()->route(...)` |
| `Account::factory()->for($user)` fails | relation method missing on the model | add the `belongsTo` / `hasMany` method |
| Migration fails on MySQL but works on SQLite | table referenced before it is created | rename the migration so it sorts earlier |
