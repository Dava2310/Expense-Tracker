# Release 3: Stay on track

| | |
|---|---|
| **Status** | Next |
| **Effort** | 6 person-weeks |
| **Requirements** | FR-038 to FR-046 |
| **Depends on** | Release 2 (budgets and recurring payments, needed by the alerts) |
| **Roadmap** | [Saldo Product Roadmap](https://claude.ai/artifact/PVdP2YiWo4B9Jr5zXQVPWk) |

## Objective

Keep users coming back and in control between visits: Saldo works well on the phone where spending happens, shows how this month compares to the past, and tells the user by email when something needs their attention.

## Success metrics

- Share of entries logged from a phone.
- Users who return at least once a week.
- Users who keep alerts turned on (a low opt-out rate means the alerts are useful, not noise).

## Features, in RICE order

| # | Feature | Reach | Impact | Confidence | Effort (wk) | RICE score |
|---|---|---|---|---|---|---|
| 3.1 | Installable mobile app (PWA) | 600 | 1 | 60% | 2 | **180** |
| 3.2 | Reports: month vs. month, year view | 400 | 1 | 80% | 2 | **160** |
| 3.3 | Email alerts and weekly summary | 500 | 1 | 60% | 2 | **150** |

Email alerts score lowest on their own, but they make budgets (FR-030 to FR-032) and recurring payments (FR-034 to FR-037) useful between visits. They need both to exist, which is why they are in this release and not earlier.

---

## 3.1 Installable mobile app (PWA)

**RICE 180** · Reach 600 · Impact 1 · Confidence 60% · Effort 2 wk

### Main objective

A user can add Saldo to their phone's home screen and log an expense in under 10 seconds, right after paying.

### Problem today

Saldo is a website built and designed at desktop width. Spending happens on the go: if logging needs a laptop, entries are forgotten. A native app is out of reach for this project, but a Progressive Web App gives most of the benefit with web code.

### What it does

- Saldo can be **installed** from the browser ("Add to Home Screen"). It opens full screen with its own icon and name, like an app.
- Every logged-in page works well at phone width (around 390px): forms are easy to fill with one hand, lists and tables don't overflow, buttons are at least 44px tall.
- On phones, the main navigation becomes a **bottom bar** (Dashboard, Expenses, Add, Incomes, More), with the Add button from FR-028 in the middle.
- When there is no connection, Saldo shows a clear "You're offline" page instead of the browser's error.

### Functional requirements

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR - 038 | Install on device | The user installs Saldo on their phone or computer from the browser. It opens in its own window with the Saldo icon, and shows an offline page when there is no connection. | - |
| FR - 039 | Mobile navigation | On small screens the user navigates with a bottom bar that includes quick add. All pages are usable at phone width. | - |

### Business rules

- No data is stored offline and nothing can be logged offline (keeps the app simple and the balances always correct).
- The service worker caches only static assets (CSS, JS, icons) and the offline page, never pages with user data.
- Logging in is required as usual; the installed app uses the same session as the browser.

### Changes

- **Public files**: `public/manifest.webmanifest` (name, short name, colours, `start_url` = dashboard, `display: standalone`), icons (192 and 512 px, maskable), `public/sw.js` (small hand-written service worker), `public/offline.html`.
- **Layout** `layouts/app.blade.php`: link the manifest, theme colour meta tags, register the service worker in `resources/js/app.js`.
- **Component** `components/saldo/top-nav.blade.php`: bottom bar version for small screens (Tailwind responsive classes).
- **Views**: review every page at 390px and fix overflow and tap target sizes.
- No new npm package (the service worker is written by hand). Adding a plugin such as a Vite PWA plugin would need approval first.
- **Design**: phone versions of the dashboard, expenses list and new expense form on the canvas.

### Out of scope

- Offline logging and sync.
- Push notifications (alerts go by email in 3.3).
- Publishing in the App Store or Google Play.

### Acceptance criteria

- [ ] Chrome on Android and Safari on iOS offer to install Saldo; once installed it opens full screen with the Saldo icon.
- [ ] At 390px wide, no logged-in page scrolls sideways and every button is easy to tap.
- [ ] Logging an expense from the bottom bar's Add button takes at most 3 taps before typing.
- [ ] With the connection off, the installed app shows the offline page.

### Tests

- Feature test: the layout includes the manifest link; `manifest.webmanifest` is served and valid JSON with the required keys.
- Manual check on a real phone (Android and iOS): install, navigation, offline page. Record the result in the PR.

---

## 3.2 Reports: month vs. month, year view

**RICE 160** · Reach 400 · Impact 1 · Confidence 80% · Effort 2 wk

### Main objective

The user can answer "Am I spending more than before, and on what?" without exporting data to a spreadsheet.

### Problem today

The dashboard shows the last 6 months in total and this month by category. There is no way to compare one month with another per category, or to see a whole year.

### What it does

A **Reports** page with two views:

**Month vs. month**

- The user picks a month (default: the current one). The page compares it with the previous month: for each category, spent this month, spent last month, the difference in money and in %.
- Categories with the biggest increase are listed first, so the important change is at the top.
- Totals for incomes, expenses and net for both months.

**Year view**

- The user picks a year (default: the current one). A table with one row per category and one column per month, plus a total column and a total row.
- A line on top with each month's income, expenses and net, and the year's totals.
- The table scrolls sideways inside its own box on small screens.

### Functional requirements

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR - 040 | Month comparison | The user compares the expenses of a month with the previous month per category, with the difference in money and percent, plus income, expense and net totals. | month (YYYY-MM) |
| FR - 041 | Year report | The user sees a year as a table of expenses by category and month, with monthly income, expense and net totals. | year |

### Business rules

- Only the user's own entries count, including those of deactivated accounts (same as the dashboard). Transfers never count.
- Percent change is not shown when last month was 0 (shown as "new" instead).
- Months in the future show as empty, not as 0, to avoid a misleading drop.
- Invalid month or year values fall back to the current one (like `PeriodFilter` does).
- Grouping is done in PHP, not with database date functions, so it behaves the same on SQLite and MySQL (CI runs both).

### Changes

- **Model** `User`: `expensesByCategoryAndMonth(int $year): Collection` and `monthComparison(CarbonInterface $month): Collection`. Reuse the approach of `monthlyFlow()` and `monthSpendingByCategory()`.
- **Controller** `Userzone\ReportController` with `month` and `year` actions (or one invokable controller with a `view` parameter, whichever stays simpler).
- **Routes**: `GET /reports` (`reports.month`) and `GET /reports/year` (`reports.year`).
- **Views**: `userzone/reports/{month,year}.blade.php`; nav entry; a "See reports" link from the dashboard's income vs. expenses block.
- **Design**: both report pages on the canvas.

### Out of scope

- Charts beyond what the dashboard already has.
- Custom date ranges or reports by account.
- Exporting reports (CSV export of entries exists, FR-033).

### Acceptance criteria

- [ ] With Groceries at 300 in October and 360 in November, the comparison shows +60 and +20% for Groceries.
- [ ] A category with spending only this month shows "new".
- [ ] The year table's totals add up across rows and columns.
- [ ] Transfers and other users' data never appear.
- [ ] `?month=banana` shows the current month without an error.

### Tests

- `ReportMonthTest`: differences and percentages, "new" case, ordering, totals, ownership, invalid input.
- `ReportYearTest`: cells, totals, future months empty, ownership, deactivated account entries included, transfers excluded.

---

## 3.3 Email alerts and weekly summary

**RICE 150** · Reach 500 · Impact 1 · Confidence 60% · Effort 2 wk

### Main objective

The user hears about what needs their attention (a low balance, a budget almost used, a payment due tomorrow) without having to open Saldo, and gets a short weekly picture that brings them back.

### Problem today

Saldo already knows when an account drops below its minimum, and from Release 2 when a budget is close to its limit or a payment is coming. But it only shows this when the user opens the app, which is often too late.

### What it does

Four kinds of email, each one can be turned on or off in the profile:

| Email | Sent when | Example |
|---|---|---|
| **Low balance** | An entry or transfer leaves an account below its minimum balance | "Wallet is at €86.40, below its €100.00 minimum." |
| **Budget alert** | A category reaches 80% of its budget, and again when it goes over 100% | "You've used 85% of your Eating out budget for March: €25.50 left." |
| **Payment reminder** | The day before a recurring expense is due | "Tomorrow: Rent €950.00 from Everyday." |
| **Weekly summary** | Every Monday morning | Last week's income, expenses and top 3 categories; budgets' state; next week's recurring payments |

Each email has a link to the right page in Saldo and a link to the notification settings.

### Functional requirements

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR - 042 | Notification settings | The user turns each kind of email on or off from the profile. All are on by default. | low_balance, budget, reminders, weekly_summary |
| FR - 043 | Low balance alert | The system emails the user when an account drops below its minimum balance. | - (event) |
| FR - 044 | Budget alert | The system emails the user when a category reaches 80% of its monthly budget, and when it goes over 100%. | - (event) |
| FR - 045 | Payment reminder | The system emails the user the day before a recurring expense is due. | - (scheduled) |
| FR - 046 | Weekly summary | The system emails the user every Monday a summary of last week and what is coming next week. | - (scheduled) |

### Business rules

- **No repeated alerts.** Each alert is sent once per occurrence:
  - Low balance: once each time the account goes from above to below its minimum. Not again until it has gone back above it.
  - Budget: at most once at 80% and once at 100%, per category and per month.
  - Reminders: once per rule and due date.
- Alerts are sent only for active accounts and active recurring rules.
- No weekly summary when the user had no activity and has nothing coming.
- Emails are sent through the queue so saving an entry never waits for the mail server.
- Users can only receive emails at a verified address. This release turns on email verification (`MustVerifyEmail` on `User`, today commented out) if it isn't already on.

### Changes

- **Migration**: notification preference columns on `users` (four booleans, default `true`), or one JSON column if simpler.
- **Model + migration** `SentAlert` (`user_id`, `key`, `sent_at`, unique `user_id + key`), for example `budget:12:2027-03:80` or `low-balance:5:<crossing date>`. Used to avoid repeats.
- **Notifications** (`php artisan make:notification`): `LowBalanceAlert`, `BudgetAlert`, `PaymentReminder`, `WeeklySummary`, all `ShouldQueue`, mail channel, using the user's currency.
- **Checks after saving**: after any change to a balance (entry create/edit/delete, transfer, recurring run), check the account minimum and the category budget. Keep it in one place, for example a `User` or `Account` method called from the controllers and the recurring command.
- **Commands** scheduled in `routes/console.php`: daily `saldo:send-reminders`, Monday morning `saldo:send-weekly-summary`.
- **Profile**: settings section in `userzone/profile` with the four toggles; `ProfileUpdateRequest` rules.
- **Config**: mail driver `log` locally; a real mail service in production (document it next to the scheduler in the README). A queue worker must run in production.
- **Design**: email templates (simple, Saldo colours), profile settings section.

### Out of scope

- Push notifications on the phone, SMS or in-app notification centre.
- Choosing the time or day of the summary.
- Alerts for incomes.

### Acceptance criteria

- [ ] An expense that takes Wallet from 120 to 86.40 (minimum 100) sends one low balance email; a second expense the same day sends nothing more.
- [ ] Reaching 80% of a budget sends one email; reaching 100% sends one more; nothing else that month.
- [ ] A recurring rent due tomorrow triggers one reminder today; running the command twice sends one email.
- [ ] Turning off an alert type stops that email.
- [ ] The weekly summary isn't sent to a user with no activity and nothing coming.

### Tests

- Use `Notification::fake()` and `travelTo()`.
- `LowBalanceAlertTest`, `BudgetAlertTest`: crossing sends once, staying below doesn't repeat, settings respected.
- `PaymentReminderTest`, `WeeklySummaryTest` (commands): who gets an email, content totals, no duplicates, settings respected.
- `ProfileTest`: preferences saved.

---

## Release checklist

- [ ] Add FR-038 to FR-046 to the requirements document.
- [ ] Design phone screens, report pages and email templates on the Saldo canvas first.
- [ ] Production: scheduler cron, queue worker and mail service documented and configured.
- [ ] Manual test on a real Android and iOS phone for 3.1.
- [ ] One branch per feature, merged in RICE order; Pint, feature tests and full suite green.
- [ ] Re-score Release 4 with real usage data if Saldo has users by then.
