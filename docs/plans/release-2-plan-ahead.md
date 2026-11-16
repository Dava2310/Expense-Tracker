# Release 2: Plan ahead

| | |
|---|---|
| **Status** | Next |
| **Effort** | 6.5 person-weeks |
| **Requirements** | FR-028 to FR-037 |
| **Depends on** | Release 1 (editing and deleting entries, user currency) |
| **Roadmap** | [Saldo Product Roadmap](https://claude.ai/artifact/PVdP2YiWo4B9Jr5zXQVPWk) |

## Objective

Move Saldo from recording the past to helping the user plan the month: logging becomes fast enough to be a habit, each category can have a monthly limit, and fixed payments such as rent and subscriptions are logged automatically and visible before they happen.

This release answers the user's question *"Can I afford this, given rent and subscriptions are still coming?"*.

## Success metrics

- Users with at least one budget set.
- Users with at least one recurring payment.
- Entries logged per active user per week goes up compared to after Release 1.

## Features, in RICE order

| # | Feature | Reach | Impact | Confidence | Effort (wk) | RICE score |
|---|---|---|---|---|---|---|
| 2.1 | Quick add and search | 700 | 1 | 80% | 1 | **560** |
| 2.2 | Monthly budgets per category | 450 | 3 | 70% | 2 | **473** |
| 2.3 | CSV export | 200 | 1 | 90% | 0.5 | **360** |
| 2.4 | Recurring payments and subscriptions | 550 | 2 | 80% | 3 | **293** |

Recurring payments come last on purpose as well: they create entries automatically, so Release 1's ability to correct or delete an entry (FR-021 to FR-024) must exist first.

---

## 2.1 Quick add and search

**RICE 560** · Reach 700 · Impact 1 · Confidence 80% · Effort 1 wk

### Main objective

Logging an entry takes a few seconds from any page, and any past entry can be found by what it was for.

### Problem today

To log an expense, the user has to go to Expenses, then New expense, then pick the account and category every time. To find "that dentist payment in March", they have to scroll the month groups. Slow logging is the main reason people stop tracking.

### What it does

**Quick add**

- An **Add** button in the top navigation, on every logged-in page, opens a small menu: *New expense*, *New income*, *New transfer*.
- After saving, the user goes back to the page they started from, with the usual success message.
- The forms **pre-fill the account and category used in the user's last entry** of that type. The date is already today's date.

**Search**

- A search box on the Expenses and Incomes lists finds entries whose concept contains the text typed (for example "dentist").
- Search works together with the existing account, category and period filters.
- The page summary says what was searched: "12 expenses matching 'uber' in the last 3 months".

### Functional requirements

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR - 028 | Quick add | From any page the user opens the form for a new expense, income or transfer. The account and category of their last entry are preselected, and after saving they return to where they were. | return URL (internal only) |
| FR - 029 | Search entries | The user searches expenses or incomes by concept, combined with the account, category and period filters. | q (text) |

### Business rules

- The return address must be an internal Saldo URL; anything else falls back to the list page (avoids open redirects).
- Pre-selection only uses the user's own, **active** accounts; if the last account was deactivated, nothing is preselected.
- Search is case-insensitive, matches part of the concept, and ignores empty or whitespace-only text. Maximum 100 characters.

### Changes

- **Component** `components/saldo/top-nav.blade.php` (and `View\Components\Saldo\TopNav`): Add button with a small Alpine dropdown of three links.
- **Controllers** `ExpenseController@create` / `IncomeController@create`: find the last entry's `account_id` and `category_id` and pass them as defaults. `store()` redirects to a validated `return` value if present.
- **Index**: in both `index()` methods add `->when($search, fn ($q) => $q->where('expenses.concept', 'like', "%{$search}%"))` to the existing filtered query.
- **Component** `components/saldo/entry-filters.blade.php`: a search field that is submitted with the other filters.
- **Design**: the Add menu in the top nav and the search field on the list artboards.

### Out of scope

- A global search across all modules.
- Searching by amount, or fuzzy matching.
- A quick-add pop-up form (the menu links to the normal create pages).

### Acceptance criteria

- [ ] The Add menu is visible and works from the dashboard, accounts, categories and list pages.
- [ ] A new expense form preselects the account and category of the user's previous expense.
- [ ] Saving from quick add returns to the starting page; an external return URL is ignored.
- [ ] Searching "uber" on Expenses shows only expenses whose concept contains "Uber" (any case), and combines with filters.

### Tests

- `ExpenseCreateTest` / `IncomeCreateTest`: defaults from the last entry; deactivated account not preselected; return URL honored; external URL ignored.
- `ExpenseIndexTest` / `IncomeIndexTest`: search matches part of the concept, is case-insensitive, combines with a filter, and doesn't show other users' entries.

---

## 2.2 Monthly budgets per category

**RICE 473** · Reach 450 · Impact 3 · Confidence 70% · Effort 2 wk

### Main objective

The user decides in advance how much they want to spend per category each month, and sees at any moment how much is left.

### Problem today

Saldo shows where money went, but only after the fact. Nothing tells the user they are about to overspend on eating out until the month is over.

### What it does

- On a category's page, the user sets a **monthly limit** (for example Groceries: 400). The same limit applies every month until changed or removed.
- A **Budgets** block on the dashboard lists every category with a budget: spent this month, limit, what's left, and a progress bar.
- The progress bar and a label show the state:

| Spent of the limit | State | Shown as |
|---|---|---|
| Below 80% | On track | Neutral bar, "€120 left" |
| 80% to 100% | Close to the limit | Warning colour, "€35 left" |
| Over 100% | Over budget | Strong colour, "€40 over" |

- The Categories list shows the budget next to each category that has one.

### Functional requirements

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR - 030 | Set budget | The user sets or changes the monthly spending limit of one of their categories. | {category_id}, amount |
| FR - 031 | Remove budget | The user removes the limit of a category. | {category_id} |
| FR - 032 | Budget progress | The user sees, for each budgeted category, what was spent this month, the limit, what is left and its state. | - |

### Business rules

- At most **one budget per category**. A budget belongs to the category's owner.
- Amount: greater than 0, two decimals, max 999,999.99 (same as entries).
- "Spent" = the sum of this calendar month's expenses in that category, across all accounts, including deactivated ones (same rule as the dashboard's spending by category).
- Budgets apply to **expenses only**; incomes and transfers don't count.
- Deleting a category also deletes its budget. A budget alone does **not** make a category "in use" (FR-017 still allows deletion).

### Changes

- **Model + migration + factory** `Budget` (`php artisan make:model Budget -mf`): `id`, `category_id` (unique, foreign key, cascade on delete), `amount` (`decimal(8,2)`), timestamps.
- **Relations**: `Category::budget()` (`hasOne`), `Budget::category()`; `User::budgets()` (`hasManyThrough` categories).
- **Model** `User`: method `budgetProgress(): Collection` returning `name`, `limit`, `spent`, `left`, `state` per budget. Reuse the query of `monthSpendingByCategory()`.
- **Controller** `Userzone\BudgetController`: `update` (create or change, `updateOrCreate`) and `destroy`, nested under categories.
- **Request** `UpdateBudgetRequest`.
- **Routes**: `PUT /categories/{category}/budget` (`budgets.update`), `DELETE /categories/{category}/budget` (`budgets.destroy`).
- **Views**: budget field on `categories/edit.blade.php`; budget column on `categories/index.blade.php`; new component `components/saldo/budget-progress.blade.php` used on the dashboard.
- **Design**: Budgets block on the dashboard artboard; budget field on Edit category.

### Out of scope

- Different limits for different months, or rolling unused budget into next month.
- Budgets for incomes, accounts or the whole month.
- Email alerts when a budget is nearly used (Release 3, FR-044).

### Acceptance criteria

- [ ] A user can set, change and remove a budget for their own category; another user's category returns 404.
- [ ] With Groceries at 400 and 320 spent this month, the dashboard shows "€80 left" with the "close to the limit" state.
- [ ] Expenses from last month, incomes and transfers don't count.
- [ ] Deleting an unused category with a budget works and removes the budget.

### Tests

- `BudgetTest`: set, update, remove; validation; other user's category; category deletion cascade.
- `DashboardTest`: progress values and each of the three states (79.99%, 80%, 100.01%).

---

## 2.3 CSV export

**RICE 360** · Reach 200 · Impact 1 · Confidence 90% · Effort 0.5 wk

### Main objective

The user can take their data out of Saldo, to open it in a spreadsheet, share it with an accountant or keep a backup.

### Problem today

There is no way to get data out. Users who need a report for taxes or a shared flat have to copy entries by hand. Being able to leave is also part of trusting an app with your money.

### What it does

- An **Export CSV** button on the Expenses list and on the Incomes list.
- It downloads exactly what the list currently shows: same account, category, period and search.
- The file is named after its content, for example `saldo-expenses-2026-11.csv` or `saldo-incomes-all-time.csv`.

Columns: `date` (YYYY-MM-DD), `concept`, `category`, `account`, `amount` (dot as decimal separator, no currency symbol), `currency`.

### Functional requirements

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR - 033 | Export entries | The user downloads their expenses or incomes as a CSV file, applying the filters currently selected on the list. | account, category, period, from, to, q |

### Business rules

- Only the user's own entries are exported.
- The export uses the same filter logic as the list page, so the file and the screen always match.
- Text is UTF-8 with a header row.

### Changes

- **Controllers**: `export()` in `ExpenseController` and `IncomeController`, returning `response()->streamDownload()` with `fputcsv`. Extract the shared filtered query of `index()` into a private method so both use it.
- **Routes**: `GET /expenses/export` (`expenses.export`), `GET /incomes/export` (`incomes.export`), declared before the `{expense}` routes.
- **Views**: Export button on both list pages, carrying the current query string.

### Out of scope

- Excel (.xlsx) or PDF files.
- Exporting accounts, categories or transfers.
- Scheduled or emailed exports.

### Acceptance criteria

- [ ] With the filters "Everyday" + "This month", the CSV has exactly the rows shown on screen.
- [ ] The file opens correctly in a spreadsheet app, including concepts with commas or accents.
- [ ] Another user's entries never appear in the file.

### Tests

- `ExpenseExportTest` / `IncomeExportTest`: download headers and filename, header row, rows match filters, no other users' data, guests redirected.

---

## 2.4 Recurring payments and subscriptions

**RICE 293** · Reach 550 · Impact 2 · Confidence 80% · Effort 3 wk

### Main objective

Fixed payments and incomes (rent, salary, streaming, gym) are logged automatically on their day, and the user always knows what is still coming this month and how much their subscriptions cost.

### Problem today

The user has to remember to log the same rent and subscriptions every month, and has no view of upcoming payments. Many people don't know how much they spend on subscriptions in total.

### What it does

**Recurring rules**

- The user creates a recurring rule: expense or income, amount, concept, account, category, how often (**monthly** or **yearly**) and the next date.
- From an existing entry, a **Make it recurring** link fills the form from that entry.
- Every day, Saldo creates the entries that are due, updates the balances and moves each rule to its next date.
- A rule can be **edited**, **paused** (stops creating entries, stays in the list), **resumed** and **deleted**. Entries already created stay.

**Upcoming and subscriptions**

- A **Recurring** page lists all rules, with the next date and state (active, paused, needs attention).
- An **Upcoming this month** block on the dashboard lists what will still be charged or received before the month ends, and the total.
- A **Subscriptions** summary on the Recurring page: all active recurring expenses with their monthly cost (yearly ones divided by 12), and the total per month and per year.

**When a payment can't be logged**

- If a due expense would make the account balance negative, Saldo does not create it. The rule is marked **needs attention**, shown on the dashboard ("Couldn't log Rent on Mar 1: Everyday only has €300.00"), and is retried every day until it works or the user changes it.

### Functional requirements

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR - 034 | Create recurring rule | The user creates a rule that logs an expense or income automatically every month or year. It can start from an existing entry. | type, amount, concept, account_id, category_id, frequency, next_date |
| FR - 035 | Read recurring rules | The user sees all rules, the payments still to come this month, and the total cost of their subscriptions. | - |
| FR - 036 | Update, pause or delete rule | The user edits, pauses, resumes or deletes a rule. Entries already created are not changed. | {recurring_id}, same fields as FR-034, active |
| FR - 037 | Automatic logging | Every day the system creates the entries of all active rules that are due, updates balances and schedules the next date. Expenses without enough balance are not created and the rule is flagged. | - (scheduled) |

### Business rules

- Frequencies: monthly and yearly. For monthly rules on days 29–31, months without that day use their **last day** (a rule on the 31st runs Feb 28, Apr 30…) and return to the 31st afterwards. Store the original day (`day_of_month`) to make this work.
- Only active accounts and the user's own categories can be used.
- A generated entry is a normal expense or income: it can be edited or deleted (FR-021 to FR-024), and it keeps a link to its rule.
- If the job didn't run for a few days, it catches up: each missed occurrence is created with its own date, oldest first.
- Each generated entry and its balance change run in one transaction. Running the job twice on the same day never creates duplicates (the rule's `next_date` only moves forward after an entry is created).
- Deactivating an account **pauses** its rules.
- A category used by a rule counts as "in use" (FR-017 blocks its deletion).

### Changes

- **Model + migration + factory** `RecurringEntry` (`php artisan make:model RecurringEntry -mf`): `id`, `user_id`, `type` (`income`/`expense`), `account_id`, `category_id`, `amount`, `concept`, `frequency` (`monthly`/`yearly`), `day_of_month`, `next_date`, `is_active`, `failed_at` (nullable), `failure_reason` (nullable), timestamps.
- **Migration**: nullable `recurring_entry_id` on `expenses` and `incomes` (`nullOnDelete`).
- **Model** `RecurringEntry`: `advance()` (computes the next date from frequency and `day_of_month`), `monthlyCost()`; relations to account, category, user.
- **Command** `php artisan make:command RunRecurringEntries` (`saldo:run-recurring`): processes due rules. Schedule it daily in `routes/console.php`.
- **Controller** `Userzone\RecurringEntryController` (resource without `show`) + `Store`/`Update` requests.
- **Model** `User`: `upcomingThisMonth()` for the dashboard block.
- **Model** `Category::isInUse()`: also check recurring rules. `AccountController@destroy`: pause the account's rules.
- **Views**: `userzone/recurring/{index,create,edit}.blade.php`; dashboard "Upcoming this month" and "Needs attention" blocks; "Make it recurring" link on the entry edit page; nav entry.
- **Design**: Recurring list (with subscriptions summary), New recurring rule, dashboard blocks.
- **Deployment**: production needs the Laravel scheduler running (one cron line for `php artisan schedule:run`). Document it in the README.

### Out of scope

- Weekly, every-two-weeks or custom frequencies.
- Recurring transfers.
- Reminders before a payment (Release 3, FR-045).
- Detecting subscriptions automatically from past entries.

### Acceptance criteria

- [ ] A monthly rent rule on the 1st creates the expense on the 1st of each month and lowers the balance once.
- [ ] Running the command twice on the same day creates no duplicates.
- [ ] A rule on the 31st runs on Feb 28 and on Mar 31.
- [ ] After 3 days without running, the missed occurrence is created with its original date.
- [ ] Without enough balance, no entry is created and the rule shows "needs attention" on the dashboard.
- [ ] Paused rules create nothing; resuming continues from the next due date.
- [ ] The subscriptions total shows a yearly €120 subscription as €10 per month.
- [ ] Deactivating an account pauses its rules.

### Tests

- `RecurringEntryTest` (CRUD, validation, ownership, "Make it recurring" prefill).
- `RunRecurringEntriesTest` (command): due/not due, monthly and yearly, end-of-month days, catch-up, idempotency, insufficient balance, paused rules. Freeze time with `travelTo()`.
- `DashboardTest`: upcoming block and needs-attention message.
- `CategoryDeleteTest`: a category used by a rule can't be deleted. `AccountDeactivateTest`: rules paused.

---

## Release checklist

- [ ] Add FR-028 to FR-037 to the requirements document.
- [ ] Design the new screens on the Saldo canvas first (Add menu, search, budgets, recurring pages, dashboard blocks).
- [ ] One branch per feature, merged in RICE order.
- [ ] Scheduler documented for deployment before 2.4 is released.
- [ ] Pint, feature tests, full suite green on SQLite and MySQL.
- [ ] Re-score Release 3 with what was learned.
