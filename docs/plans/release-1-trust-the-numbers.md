# Release 1: Trust the numbers

| | |
|---|---|
| **Status** | Now (next release to build) |
| **Effort** | 4.5 person-weeks |
| **Requirements** | FR-018 to FR-027 |
| **Depends on** | MVP (FR-001 to FR-017) |
| **Roadmap** | [Saldo Product Roadmap](https://claude.ai/artifact/PVdP2YiWo4B9Jr5zXQVPWk) |

## Objective

Make every number Saldo shows correct, and let a new user log their first entry in their first session.

Release 1 adds no new modules. It fixes the four product gaps found after the MVP, because each one gives a real user a reason to stop using Saldo in the first week:

1. A new user has no categories, so they cannot log an expense until they create one.
2. Every amount is shown in euros, whatever currency the user lives in.
3. A wrong amount, date or account can never be corrected, and nothing can be deleted.
4. Moving money between your own accounts has to be logged as a fake expense plus a fake income, which inflates the dashboard chart.

## Success metrics

- Most new users log an entry during their first session.
- No user needs to create a paired expense + income to move money between accounts.
- No one has to create a new account to work around a wrong entry.

## Features, in RICE order

| # | Feature | Reach | Impact | Confidence | Effort (wk) | RICE score |
|---|---|---|---|---|---|---|
| 1.1 | Starter categories at sign-up | 400 | 2 | 90% | 0.5 | **1440** |
| 1.2 | Choose your currency | 400 | 3 | 80% | 1 | **960** |
| 1.3 | Correct or delete an entry | 700 | 2 | 90% | 1.5 | **840** |
| 1.4 | Transfers between accounts | 600 | 2 | 90% | 1.5 | **720** |

Build them in this order. The order also works technically: 1.2 replaces the hard-coded currency before 1.3 and 1.4 add new screens with amounts on them.

---

## 1.1 Starter categories at sign-up

**RICE 1440** · Reach 400 · Impact 2 · Confidence 90% · Effort 0.5 wk

### Main objective

A user can log their first expense or income right after registering, without first visiting the Categories page.

### Problem today

Registration (`RegisteredUserController@store`) creates only the user. Expenses and incomes require a `category_id` that belongs to the user (`StoreExpenseRequest`). So a new user's first attempt to log an expense hits an empty category dropdown.

### What it does

When a user registers, Saldo creates a short list of common categories for them. They are ordinary categories: the user can rename, edit or delete them like any other (FR-015, FR-017).

Starter list (name and description):

| Name | Description |
|---|---|
| Groceries | Supermarket and food shopping |
| Rent | Rent or mortgage payments |
| Utilities | Electricity, water, gas, internet, phone |
| Transport | Public transport, fuel, taxis |
| Eating out | Restaurants, cafés, delivery |
| Health | Pharmacy, doctors, insurance |
| Subscriptions | Streaming, apps, memberships |
| Shopping | Clothes, home and other purchases |
| Salary | Regular pay from work |
| Freelance | Income from side jobs or clients |

Also, when the expense or income form has no categories to offer (the user deleted them all), the form shows a short message with a link to create a category instead of an empty dropdown.

### Functional requirements

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR - 018 | Starter categories | When a user registers, the system creates a default list of categories owned by that user. They can be edited and deleted like any other category. | - (triggered by FR-001) |

### Business rules

- Starter categories are created only once, at registration. Existing users are not changed.
- They belong to the new user (`user_id`), exactly like categories created by hand.
- Creating the user and the categories happens in one database transaction: a user never ends up half-created.

### Changes

- **Model** `Category`: a constant with the starter list, for example `Category::STARTER`, as an array of `['name' => ..., 'description' => ...]`.
- **Model** `User`: a method `createStarterCategories(): void` that runs `$this->categories()->createMany(Category::STARTER)`.
- **Controller** `Auth\RegisteredUserController@store`: wrap `User::create()` and `createStarterCategories()` in `DB::transaction()`.
- **View** `components/saldo/entry-form.blade.php`: empty state when `$categories` is empty, linking to `route('categories.create')`.
- **Seeder**: optional. `DatabaseSeeder` can call `createStarterCategories()` for the seeded users so the demo data looks realistic. If it does, update `DatabaseSeederTest`.

### Out of scope

- Separate income and expense categories (categories have no type today; keep it that way).
- Letting the user choose which starter categories to keep during sign-up.

### Acceptance criteria

- [ ] After registering, the user has the 10 starter categories, and nobody else's categories changed.
- [ ] The expense form right after sign-up offers those categories.
- [ ] A starter category can be renamed and, if unused, deleted.
- [ ] With zero categories, the entry form shows a link to create one.

### Tests

- `tests/Feature/Auth/RegistrationTest.php`: registering creates the starter categories for that user only.
- `ExpenseCreateTest` / `IncomeCreateTest`: empty state when the user has no categories.

---

## 1.2 Choose your currency

**RICE 960** · Reach 400 · Impact 3 · Confidence 80% · Effort 1 wk

### Main objective

Every amount in Saldo is shown in the currency the user actually uses.

### Problem today

`'EUR'` is hard-coded in 26 view lines (`Number::currency($amount, 'EUR', 'en')`) and in the validation message of `StoreExpenseRequest`. A user in Mexico, the US or the UK sees `€` next to every amount, which makes the product feel broken for them.

### What it does

- At registration, the user picks their currency from a short list (default: EUR).
- On the profile page, the user can change it later.
- Every amount on every page uses that currency's symbol and format.

Changing the currency **only changes how amounts are displayed**. It does not convert any amount. The profile page says this next to the field.

Supported currencies (a short, fixed list is enough for the project): EUR, USD, GBP, MXN, COP, ARS, CLP, PEN, BRL, VES.

### Functional requirements

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR - 019 | Currency at sign-up | The user chooses the currency they use when registering. EUR is preselected. | currency |
| FR - 020 | Change currency | The user changes their currency from the profile page. Amounts are not converted, only displayed in the new currency. | currency |

### Business rules

- One currency per user. All their accounts use it (several currencies at once is a parked feature).
- Only currencies in the supported list are accepted.
- Users created before this release get `EUR`, so nothing changes for them.

### Changes

- **Migration**: add `currency` (`char(3)`, default `'EUR'`) to `users`.
- **Model** `User`: add `currency` to `$fillable`; constant `User::CURRENCIES` with the supported codes and names.
- **Middleware** (new, for example `UseUserCurrency`), registered for the `auth` group: sets the default currency for the request with `Number::useCurrency($request->user()->currency)`. Confirm the method with `search-docs` before using it.
- **Views**: replace every `Number::currency($x, 'EUR', 'en')` with `Number::currency($x, locale: 'en')`, so the default currency applies. One search-and-replace across `resources/views`.
- **Request** `StoreExpenseRequest`: same change in the "only has … available" message.
- **Registration**: currency select in `auth/register.blade.php`, rule `Rule::in(array_keys(User::CURRENCIES))` in `RegisteredUserController`.
- **Profile**: currency select in the profile information form; rule in `ProfileUpdateRequest`.

### Out of scope

- Converting amounts between currencies.
- Different currencies per account.
- Locale-specific number formats (keep `en`: `1,234.56`).

### Acceptance criteria

- [ ] A new user who picks USD sees `$` on the dashboard, accounts, incomes and expenses.
- [ ] Changing the currency in the profile changes the symbol everywhere and keeps the same numbers.
- [ ] An unsupported code (for example `XYZ`) is rejected at sign-up and in the profile.
- [ ] Existing users still see EUR.
- [ ] `grep -rn "'EUR'" resources/views app` returns nothing.

### Tests

- `RegistrationTest`: currency stored; invalid currency rejected.
- `ProfileTest`: currency can be changed; invalid one rejected.
- `DashboardTest` (or one other page test): an amount renders with the user's currency symbol.
- Existing tests that assert `€` keep passing because the default stays EUR.

---

## 1.3 Correct or delete an entry

**RICE 840** · Reach 700 · Impact 2 · Confidence 90% · Effort 1.5 wk

### Main objective

A user can fix any mistake in an expense or income, and the account balances stay correct afterwards.

### Problem today

FR-009 and FR-012 only allow editing the **concept**. A wrong amount, date, account or category stays in the balance forever, and an entry logged twice can't be removed. The only workaround is a fake opposite entry, which distorts the reports.

### What it does

- The edit page of an expense or income lets the user change **amount, concept, date, account and category**.
- A **Delete** button on the edit page opens a confirmation dialog (`x-saldo.modal`). Confirming removes the entry.
- After an edit or delete, the affected account balances are recalculated so they match what happened.

How balances change:

| Action | Effect on balance |
|---|---|
| Edit expense amount from 50 to 80 | Account goes down 30 more |
| Move expense from Account A to Account B | A gets the amount back, B pays it |
| Delete an expense | Account gets the amount back |
| Edit income amount from 100 to 60 | Account goes down 40 |
| Delete an income | Account goes down by the income amount |

### Functional requirements

These replace FR-009 and FR-012 of the MVP.

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR - 021 | Update expense | The user edits any field of an expense. The balances of the old and new account are adjusted. | {expense_id}, amount, concept, date, account_id, category_id |
| FR - 022 | Update income | The user edits any field of an income. The balances of the old and new account are adjusted. | {income_id}, amount, concept, date, account_id, category_id |
| FR - 023 | Delete expense | The user deletes an expense after confirming. Its amount returns to the account. | {expense_id} |
| FR - 024 | Delete income | The user deletes an income after confirming. Its amount is removed from the account. | {income_id} |

### Business rules

- **No negative balances**, the same rule as creating an expense (`Account::hasEnoughBalanceFor`). An edit or delete that would leave any account below 0 is rejected with a message such as "Everyday would drop to −€20.00. Lower the amount or pick another account."
  - For an expense edit on the same account, the check uses the balance *after* giving back the old amount.
  - Reducing or deleting an income can also make a balance negative; the same check applies.
- Entries on a **deactivated account** are history: they can be viewed but not edited or deleted. The edit page shows them read-only (`x-saldo.entry-detail`).
- An entry can only be moved to one of the user's **active** accounts, and only use the user's own categories (same rules as `StoreExpenseRequest`).
- Each edit or delete, with its balance changes, runs in **one database transaction**. If any part fails, nothing changes.
- Only the owner can edit or delete (`ensureOwnedBy` already returns 404 for others).

### Changes

- **Requests** `UpdateExpenseRequest` / `UpdateIncomeRequest`: same rules as the Store requests, plus the balance check in `after()` that accounts for the old values.
- **Controllers** `ExpenseController@update` / `IncomeController@update`: in a transaction, undo the old effect (increment/decrement the old account), save, apply the new effect. Add `destroy()` to both.
- **Routes**: `DELETE /expenses/{expense}` (`expenses.destroy`) and `DELETE /incomes/{income}` (`incomes.destroy`).
- **Views** `expenses/edit.blade.php`, `incomes/edit.blade.php`: use the full `x-saldo.entry-form` in edit mode, a Delete button and confirmation modal. Flash messages `expense-updated`, `expense-deleted`, etc.
- **Design**: add "Edit expense" and "Delete expense · confirm" artboards to the canvas, next to Expense detail.

### Out of scope

- An undo button after delete.
- An edit history or audit log.
- Editing entries on deactivated accounts.

### Acceptance criteria

- [ ] Changing an expense amount from 50 to 80 lowers the balance by 30 more.
- [ ] Moving an expense to another account gives the amount back to the old one and charges the new one.
- [ ] Deleting an expense returns its amount; deleting an income removes its amount.
- [ ] An edit or delete that would make a balance negative is rejected and changes nothing.
- [ ] Entries on deactivated accounts can't be edited or deleted (403 or 404, and nothing changes).
- [ ] Another user's entry returns 404.

### Tests

- Extend `ExpenseUpdateTest` and `IncomeUpdateTest`: each balance case in the table above, same and different account, negative-balance rejection, deactivated account, other user.
- New `ExpenseDeleteTest` and `IncomeDeleteTest`: balance restored, row removed, rejected cases change nothing.

---

## 1.4 Transfers between accounts

**RICE 720** · Reach 600 · Impact 2 · Confidence 90% · Effort 1.5 wk

### Main objective

A user can move money between their own accounts without it counting as spending or income.

### Problem today

Paying the credit card from checking, or putting money into savings, can only be recorded as an expense on one account plus an income on the other. The dashboard chart and category totals then show spending and income that never happened.

### What it does

- A **New transfer** form: from account, to account, amount, date and an optional note.
- Saving it takes the amount out of one account and puts it into the other.
- Transfers appear in the activity of both accounts ("Transfer to Savings", "Transfer from Everyday").
- A **Transfers** page lists all transfers, newest first, with the same period and account filters as incomes and expenses.
- A transfer can be deleted (after confirmation), which moves the money back.
- Transfers are **not** incomes or expenses: they don't appear in the Incomes/Expenses lists, the dashboard chart or the spending by category.

### Functional requirements

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR - 025 | Create transfer | The user moves an amount from one of their active accounts to another. Both balances update. | from_account_id, to_account_id, amount, date, note |
| FR - 026 | Read transfers | The user sees all their transfers, filtered by period and account, and in each account's activity. | - |
| FR - 027 | Delete transfer | The user deletes a transfer after confirming. The money returns to the origin account. | {transfer_id} |

### Business rules

- Origin and destination must be **different**, **active** accounts owned by the user.
- The origin needs enough balance (`hasEnoughBalanceFor`).
- Deleting a transfer is rejected if the destination no longer has enough balance to give the money back.
- Transfers can't be edited. To fix one, delete it and create a new one (keeps the code simple).
- Creating and deleting run in one transaction, with both balance updates.
- Transfers involving a deactivated account stay in the history and can't be deleted.

### Changes

- **Model + migration + factory** `Transfer` (`php artisan make:model Transfer -mf`): `id`, `from_account_id`, `to_account_id` (foreign keys to `accounts`), `amount` (`decimal(8,2)`), `date`, `note` (nullable string), timestamps.
- **Relations**: `Transfer::from()` / `to()` (`belongsTo` Account, `withTrashed`); `Account::transfersOut()` / `transfersIn()`; `User::transfers()` (through accounts, like `User::expenses()`).
- **Controller** `Userzone\TransferController`: `index`, `create`, `store`, `destroy`. Reuse `App\Support\PeriodFilter`.
- **Request** `StoreTransferRequest`: amount rules as expenses; `to_account_id` `different:from_account_id`; both accounts `exists` with `user_id` and `whereNull('deleted_at')`; balance check in `after()`.
- **Routes**: `transfers.index`, `transfers.create`, `transfers.store`, `transfers.destroy`.
- **Model** `Account::recentActivity()`: include transfers in and out, with type `transfer`.
- **Views**: `userzone/transfers/{index,create}.blade.php`; a "Transfer" button on the account detail page; nav entry (top nav or inside Accounts).
- **Design**: Transfers list and New transfer artboards on the canvas.
- **Seeder**: one or two transfers per seeded user.

### Out of scope

- Transfers to other people or external accounts.
- Editing a transfer.
- Recurring transfers (could come with release 2's recurring payments later).

### Acceptance criteria

- [ ] A transfer of 200 from Everyday to Savings lowers Everyday by 200 and raises Savings by 200.
- [ ] The dashboard chart and spending by category don't change after a transfer.
- [ ] Same origin and destination, an inactive or foreign account, or not enough balance are rejected and nothing changes.
- [ ] The transfer shows in both accounts' activity and on the Transfers page.
- [ ] Deleting a transfer restores both balances.

### Tests

- `TransferCreateTest`: happy path with both balances, each validation case, other users' accounts, guests.
- `TransferIndexTest`: lists own transfers only, filters by account and period.
- `TransferDeleteTest`: balances restored; rejected when the destination can't give the money back.
- `DashboardTest`: a transfer does not count as income or expense.
- `AccountShowTest`: transfers appear in the activity.

---

## Release checklist

- [ ] Add FR-018 to FR-027 to the requirements document (new "Release 1" section), marking FR-009 and FR-012 as replaced by FR-021 and FR-022.
- [ ] Design the new and changed screens on the Saldo design canvas before building them.
- [ ] One branch per feature (`feat/starter-categories`, `feat/user-currency`, `feat/edit-delete-entries`, `feat/transfers`), merged in RICE order.
- [ ] Each feature: Pint, its tests, then the full suite (`php artisan test --compact`) green, on SQLite and MySQL in CI.
- [ ] After the release: re-score Release 2 in the roadmap with what was learned.
