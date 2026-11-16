# Release 4: The whole picture

| | |
|---|---|
| **Status** | Later |
| **Effort** | 9 person-weeks |
| **Requirements** | FR-047 to FR-055 |
| **Depends on** | Release 1 (transfers, used by debts and savings goals) |
| **Roadmap** | [Saldo Product Roadmap](https://claude.ai/artifact/PVdP2YiWo4B9Jr5zXQVPWk) |

## Objective

Cover the parts of a person's finances that Saldo doesn't track yet: their history from before they started using Saldo, money owed between people, and what they are saving for.

These features help fewer users or cost more to build than earlier releases, which is why they come last. They are the ones that turn Saldo from an expense tracker into a full picture of someone's money.

## Success metrics

- New users who import past months during their first week.
- Users with at least one open debt, and debts marked as settled.
- Users with a savings goal, and goals reached.

## Features, in RICE order

| # | Feature | Reach | Impact | Confidence | Effort (wk) | RICE score |
|---|---|---|---|---|---|---|
| 4.1 | Import a bank statement (CSV) | 350 | 3 | 50% | 4 | **131** |
| 4.2 | Debts and loans | 250 | 2 | 60% | 3 | **100** |
| 4.3 | Savings goals | 300 | 1 | 60% | 2 | **90** |

Debts and savings goals both build on transfers (FR-025 to FR-027): paying back a loan and putting money aside are movements of money that are not spending or income.

---

## 4.1 Import a bank statement (CSV)

**RICE 131** · Reach 350 · Impact 3 · Confidence 50% · Effort 4 wk

### Main objective

A user can bring months of past movements into Saldo in one go, instead of typing them one by one, so Saldo is useful from day one.

### Problem today

A new user starts with empty charts and reports. Typing the last few months by hand takes hours, so most people never do it, and the reports stay empty for weeks. Almost every bank lets customers download their movements as CSV.

Confidence is 50% because bank CSV formats vary a lot. The plan keeps the importer simple and lets the user tell Saldo which column is which.

### What it does

The import is a three-step flow:

1. **Upload**: the user picks the account the movements belong to and uploads a CSV file.
2. **Match columns**: Saldo shows the first rows of the file. The user says which column is the **date**, the **concept** and the **amount** (or two columns: money in / money out), and the date format (DD/MM/YYYY, MM/DD/YYYY or YYYY-MM-DD).
3. **Review and confirm**: Saldo shows every row as it will be saved: date, concept, amount, expense or income (negative amounts are expenses), and a category. The user picks a default category and can change it per row. Rows that look like duplicates of existing entries are unticked. The user confirms and the entries are created.

At the end, Saldo shows how many expenses and incomes were imported and how many rows were skipped.

### Functional requirements

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR - 047 | Upload and match columns | The user uploads a CSV file for one of their accounts and indicates which columns hold the date, concept and amount, and the date format. | account_id, file, column mapping, date_format |
| FR - 048 | Review and import | The user reviews the rows, assigns categories, unticks rows to skip and confirms. The system creates the expenses and incomes and updates the balance. | rows (include, category_id) |

### Business rules

- CSV only, up to 1 MB and 2,000 rows. Comma or semicolon separators are both accepted.
- Rows with a missing or invalid date or amount are shown as errors and skipped.
- Amounts: negative = expense, positive = income (or, with two columns, the "out" column = expense). Zero rows are skipped.
- **Duplicates**: a row is marked as a likely duplicate if an entry already exists on that account with the same date, amount and concept. Duplicates are unticked by default; the user can still include them.
- The whole import runs in **one transaction**: either every selected row is created or none is.
- The import keeps Saldo's rule that a balance can't go negative: rows are applied in date order, and if the balance would drop below 0 at any point, the import is rejected and the message names the first row that fails. The user can adjust the account balance or untick rows.
- Imported entries are normal expenses and incomes (they can be edited and deleted) and are marked with the import they came from.
- The uploaded file is not kept after the import finishes.

### Changes

- **Model + migration** `Import` (`id`, `user_id`, `account_id`, `filename`, `rows_imported`, `rows_skipped`, timestamps); nullable `import_id` on `expenses` and `incomes`.
- **Class** in `app/Support` (next to `PeriodFilter`), for example `BankStatement`, that reads a CSV with PHP's `fgetcsv`, applies a column mapping and returns rows. No new package.
- **Controller** `Userzone\ImportController`: `create` (upload), `map` (column matching), `preview` (review), `store` (confirm). Keep the parsed rows in the session between steps.
- **Requests** for each step (file rules: `file`, `mimes:csv,txt`, `max:1024`).
- **Routes** under `/imports`.
- **Views**: three step pages and the result page; an "Import movements" link on the account detail page and in the dashboard's empty states.
- **Design**: the three steps on the canvas, including the duplicate and error states.

### Out of scope

- Excel, OFX, QIF or PDF statements.
- Automatic category suggestions.
- Remembering the column mapping per bank (could be added later if users import often).
- Connecting to banks directly (parked: requires an open-banking provider).

### Acceptance criteria

- [ ] A CSV with 50 rows imports 50 entries on the chosen account and updates its balance correctly.
- [ ] Two-column (in/out) and one-column (signed) files both work.
- [ ] A row identical to an existing entry is unticked as a duplicate.
- [ ] Rows with an invalid date are listed as errors and skipped; the rest import.
- [ ] If the balance would go negative at any row, nothing is imported and the failing row is named.
- [ ] A file over 1 MB or with more than 2,000 rows is rejected with a clear message.

### Tests

- Unit-style tests for the CSV reader class with small fixture files in `tests/Fixtures` (date formats, separators, signed vs. two columns, bad rows).
- `ImportTest` (feature): the full flow with `UploadedFile::fake()`, duplicates, negative balance rejection, ownership, file limits.

---

## 4.2 Debts and loans

**RICE 100** · Reach 250 · Impact 2 · Confidence 60% · Effort 3 wk

### Main objective

The user always knows how much they owe and how much they are owed, to whom, and how much is left after each payment.

### Problem today

Money lent to a friend or borrowed from family, a personal loan or splitting a trip has nowhere to live in Saldo. If the user logs it as an expense or income, the reports show spending or income that isn't real.

### What it does

- A **Debts** page with two sections: **I owe** and **Owed to me**, each with its total.
- The user creates a debt: direction (I owe / owed to me), person or institution, concept, original amount, optional due date.
- Optionally, the money moved at the start: "I received the €500 into Everyday" (when borrowing) or "I paid the €200 from Everyday" (when lending). This updates the account balance without counting as income or expense.
- The user records **payments** (full or partial) against a debt: amount, date and the account the money goes out of or into. The remaining amount updates.
- When the remaining amount reaches 0, the debt is marked **settled** and moves to a "Settled" section. A debt can also be marked settled by hand (for example, forgiven).
- Debts with a due date in the past and an amount left are shown as **overdue**.
- Debt payments appear in the account's activity ("Payment to Ana · Trip to Lisbon").

### Functional requirements

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR - 049 | Create debt | The user records money they owe or are owed, optionally with the account the money initially entered or left. | direction, person, concept, amount, due_date, account_id |
| FR - 050 | Read debts | The user sees open debts by direction with their totals and remaining amounts, overdue debts, and settled ones. | - |
| FR - 051 | Record payment | The user records a full or partial payment of a debt with the account involved. The remaining amount and the account balance update. | {debt_id}, amount, date, account_id |
| FR - 052 | Settle or delete debt | The user marks a debt as settled, or deletes it after confirming. Deleting removes its payments and reverts their effect on balances. | {debt_id} |

### Business rules

- Debt payments and initial movements are **not** incomes or expenses: like transfers, they change balances but never appear in the Incomes/Expenses lists, the dashboard chart, budgets or reports.
- A payment can't be larger than the remaining amount.
- Paying a debt I owe needs enough balance in the account (no negative balances).
- Only active accounts can be used for new payments.
- Each payment and its balance change run in one transaction.
- Deleting a debt reverts all its balance effects; it is rejected if that would make an account negative.

### Changes

- **Models + migrations + factories** `Debt` (`user_id`, `direction`, `person`, `concept`, `amount`, `due_date` nullable, `settled_at` nullable, `account_id` nullable for the initial movement, timestamps) and `DebtPayment` (`debt_id`, `account_id`, `amount`, `date`, timestamps).
- **Model** `Debt`: `remaining()`, `isOverdue()`, `isSettled()`.
- **Model** `Account::recentActivity()`: include debt movements, as done for transfers.
- **Controllers** `Userzone\DebtController` (index, create, store, edit, update, destroy) and `Userzone\DebtPaymentController` (store, destroy), with Form Requests.
- **Routes** `debts.*` and `debts.payments.*`.
- **Views**: `userzone/debts/{index,create,edit}.blade.php` with the payment form on the debt page; dashboard line "You owe €X · You're owed €Y" when there are open debts.
- **Design**: Debts list, New debt and Debt detail with payments on the canvas.

### Out of scope

- Interest calculation and payment schedules for bank loans.
- Splitting expenses between several people (Splitwise-style groups).
- Sending reminders to the other person.

### Acceptance criteria

- [ ] Borrowing €500 into Everyday raises Everyday by 500, without showing as income anywhere.
- [ ] A €200 payment on a €500 debt leaves €300 remaining and lowers the chosen account by 200.
- [ ] A payment larger than the remaining amount is rejected.
- [ ] Paying the last €300 marks the debt as settled.
- [ ] A debt past its due date with money left shows as overdue.
- [ ] Deleting a debt reverts its effect on balances.

### Tests

- `DebtTest`: create with and without initial movement, ownership, validation, overdue state, delete and balance revert.
- `DebtPaymentTest`: partial and full payments, overpayment rejected, insufficient balance, settled state, inactive account.
- `DashboardTest`: debts don't change the chart or spending by category.

---

## 4.3 Savings goals

**RICE 90** · Reach 300 · Impact 1 · Confidence 60% · Effort 2 wk

### Main objective

The user sets a target ("Trip to Japan: €3,000 by July"), sees how close they are, and knows how much to save each month to get there on time.

### Problem today

Users can keep a savings account in Saldo, but nothing says what that money is for or whether they are on track.

### What it does

- The user creates a goal: name, target amount, optional target date, and the **account that holds the savings** (for example "Travel fund").
- Progress = that account's current balance against the target. Putting money into the goal means making a transfer into that account (FR-025), which keeps the logic simple: no separate contributions to track.
- Each goal shows: saved so far, % reached, what's left and, when there is a target date, **how much to save per month** to reach it in time.
- A **Goals** block on the dashboard shows the active goals with their progress bars.
- When the balance reaches the target, the goal is marked **reached** (and stays visible with a "Reached" label until archived).

### Functional requirements

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR - 053 | Create goal | The user creates a savings goal linked to one of their accounts, with a target amount and an optional target date. | name, target_amount, target_date, account_id |
| FR - 054 | Goal progress | The user sees each goal's saved amount, percent reached, amount left and, if dated, the amount to save per month. | - |
| FR - 055 | Update, archive or delete goal | The user edits a goal, archives it when it is reached or no longer needed, or deletes it. | {goal_id}, same fields as FR-053 |

### Business rules

- One goal per account (the account's balance is the goal's progress; two goals on one account would count the same money twice).
- Monthly amount needed = what's left ÷ the number of months until the target date (at least 1). Not shown if the date has passed or the goal is reached.
- Target amount: greater than 0, max 999,999.99. Target date, if given, must be in the future when the goal is created.
- If the linked account is deactivated, the goal is archived automatically.
- Deleting a goal never changes any balance.

### Changes

- **Model + migration + factory** `Goal` (`user_id`, `account_id` unique, `name`, `target_amount`, `target_date` nullable, `archived_at` nullable, timestamps).
- **Model** `Goal`: `saved()`, `progress()`, `left()`, `monthlyAmountNeeded()`, `isReached()`.
- **Controller** `Userzone\GoalController` (index, create, store, edit, update, destroy) + Form Requests.
- **Routes** `goals.*`.
- **Views**: `userzone/goals/{index,create,edit}.blade.php`; dashboard Goals block; on the account detail page, show the goal linked to that account and a "Add money" button that opens the transfer form with this account as the destination.
- **AccountController@destroy**: archive the account's goal.
- **Design**: Goals list, New goal and the dashboard block on the canvas.

### Out of scope

- Goals spread across several accounts.
- Automatic monthly transfers into a goal (a recurring transfer could come later).
- Shared goals with other people.

### Acceptance criteria

- [ ] A €3,000 goal on Travel fund with €1,200 in it shows 40% and €1,800 left.
- [ ] With 6 months to the target date, it shows €300 per month needed.
- [ ] A transfer of €300 into Travel fund updates the goal's progress.
- [ ] A second goal on the same account is rejected.
- [ ] Reaching the target shows "Reached"; deactivating the account archives the goal.

### Tests

- `GoalTest`: create, validation (one per account, future date, ownership), progress and monthly amount calculations, reached state, archive on account deactivation, delete leaves balances unchanged.
- `DashboardTest`: Goals block.

---

## After Release 4

The parked features from the roadmap are reconsidered here, with real usage data if Saldo has users by then:

| Feature | Why it was parked |
|---|---|
| Several currencies at once | Needs exchange rates and converted totals; one currency per user (FR-019) covers most people. |
| Receipt photos | Rarely needed for personal finance; adds file storage. |
| Automatic bank sync | Needs an open-banking provider, contracts and compliance. CSV import (4.1) covers part of the need. |
| Shared household accounts | Changes the ownership model of every table; needs its own discovery. |

## Release checklist

- [ ] Add FR-047 to FR-055 to the requirements document.
- [ ] Design the import flow, debts and goals on the Saldo canvas first.
- [ ] One branch per feature, merged in RICE order; Pint, feature tests and full suite green on SQLite and MySQL.
- [ ] Re-score the parked features and plan the next roadmap.
