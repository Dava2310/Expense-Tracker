# Requirement Engineering

This section aims to provide a clear understanding of the project's scope and specifications. It describes the intended users and lists the functional and non-functional requirements, demonstrating how the final product effectively addresses these needs.

## Functional Requirements

The functional requirements define all the logic business actions that the application/system is able to perform, with or without user interaction. The functional requirements are going to be divided into **functional modules**, in order to separate them depending on the domain they are working on.

### *Authentication Module*

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR \- 001 | Register | The user registers a new user account in the system. Duplicated emails should be checked. | name, email, password, password\_confirmation |
| FR \- 002 | Login | The user joins into the system with his credentials.  | email, password |
| FR \- 003 | Logout | The user should be able to logout from the system | \- |
| FR \- 018 | Reset password | The user who forgot the password asks for a reset link by email and chooses a new password with it. | email. Then {token}, email, password, password\_confirmation |
| FR \- 019 | Change password | The signed-in user changes the password, after confirming the current one. | current\_password, password, password\_confirmation |

### *Accounts Module*

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR \- 004 | Create | The user registers a new bank account in the system. The account is held in one of the user's currencies, and its balance starts at 0: it only changes through incomes, expenses and transfers. | name, bank, details, currency\_id, min\_balance |
| FR \- 005  | Update | The user updates one bank account already created. The balance and the currency can not be edited.  | {account\_id}, name, bank, details, min\_balance |
| FR \- 006 | Read | The user sees all accounts and details of one specific account. | {account\_id} for one account. None for all. |
| FR \- 007 | Delete | The user deactivates one bank account. It disappears from the list of accounts and it is not offered for new incomes, expenses or transfers, but its history is kept. | {account\_id} |
| FR \- 020 | Minimum balance warning | The system marks every account whose balance is under its minimum balance, in the accounts list and in the dashboard. It is only a warning: it does not stop any operation. | \- |
| FR \- 021 | Account activity | The detail of an account shows its latest incomes, expenses and transfers, newest first, and the income and expense totals of the current month. | {account\_id} |

### *Expenses Module*

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR \- 008 | Create | The user creates a new expense, and it automatically deducts the balance from the account. The amount must be greater than 0, with up to two decimals. The expense is refused if the account does not have enough balance, so an account never goes below zero. | amount, concept, date, account\_id, category\_id |
| FR \- 009  | Update | The user updates every detail of one expense. The balances of the accounts involved follow the change, and the change is refused if it would leave an account below zero. | {expense\_id}, amount, concept, date, account\_id, category\_id |
| FR \- 010 | Read | The user sees all the expenses, grouped by month, and should be able to filter by account, category and period, and read full content of one specific expense. | {expense\_id} for one specific expense. None for every. Optional: account, category, period |
| FR \- 022 | Delete | The user deletes one expense after confirming it, and its amount goes back to the account. The deletion can not be undone. | {expense\_id} |

### *Incomes Module*

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR \- 011 | Create | The user creates a new income, and it automatically increments the balance from the account. The amount must be greater than 0, with up to two decimals. | amount, concept, date, account\_id, category\_id |
| FR \- 012  | Update | The user updates every detail of one income. The balances of the accounts involved follow the change, and the change is refused if it would leave an account below zero. | {income\_id}, amount, concept, date, account\_id, category\_id |
| FR \- 013 | Read | The user sees all the income, grouped by month, and should be able to filter by account, category and period, and read full content of one specific income. | {income\_id} for one specific income. None for every. Optional: account, category, period |
| FR \- 023 | Delete | The user deletes one income after confirming it, and its amount is taken out of the account. The deletion is refused if the account would go below zero, for example when the money was already spent. The deletion can not be undone. | {income\_id} |

### *Categories Module*

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR \- 014 | Create | The user registers a new category. Two categories of the same user can not have the same name, whatever the letter case. | name, details |
| FR \- 015  | Update | The user updates one category. | {category\_id}, name, details |
| FR \- 016 | Read | The user sees all categories and data for specific ones. | {category\_id} for one category. None for all. |
| FR \- 017 | Delete | The user deletes one category. Note: It can not be deleted if the category has been already associated with an expense/income. | {category\_id} |
| FR \- 024 | Starter categories | When a user registers, the system creates a set of common categories (for example Groceries, Rent, Transport, Salary and Freelance), so that incomes and expenses can be recorded right away. | \- |

### *Currencies Module*

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR \- 025 | Create | The user registers a new currency, to hold accounts in it. | name, symbol |
| FR \- 026 | Update | The user updates one currency. | {currency\_id}, name, symbol |
| FR \- 027 | Read | The user sees all currencies with the number of accounts that use them, and the detail of one currency with its accounts (active and inactive) and their total balance. | {currency\_id} for one currency. None for all. |
| FR \- 028 | Delete | The user deletes one currency. Note: It can not be deleted if any account, active or inactive, uses it. | {currency\_id} |

### *Transfers Module*

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR \- 029 | Create | The user moves money from one of the accounts to another one. The amount is deducted from the first account and added to the second one, so the total across accounts does not change. A transfer is neither an income nor an expense: it has no category and it is left out of the income and expense totals, the category breakdown and the dashboard charts. Both accounts must be different, active, owned by the user and held in the same currency. The transfer is refused if the first account does not have enough balance. | from\_account\_id, to\_account\_id, amount, date, note |
| FR \- 030 | Update | The user updates every detail of one transfer. The balances of the accounts involved follow the change, and the change is refused if it would leave an account below zero. | {transfer\_id}, from\_account\_id, to\_account\_id, amount, date, note |
| FR \- 031 | Delete | The user deletes one transfer after confirming it, and the amount goes back from the second account to the first one. The deletion is refused if the second account would go below zero, for example when the money was already spent. The deletion can not be undone. | {transfer\_id} |
| FR \- 032 | Second account needed | When the user has fewer than two active accounts, the transfer page explains that a second account is needed and links to creating one, instead of showing the form. | \- |
| FR \- 033 | History | The history of each account shows the transfer once, naming the other account (for example "Transfer to Savings" or "Transfer from Checking"), and links to its edit page. | {account\_id} |

### *Dashboard Module*

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR \- 034 | Account balances | The dashboard shows the balance of every active account with its minimum balance and the warning of FR \- 020, and lists the inactive accounts apart. | \- |
| FR \- 035 | Income and expenses chart | The dashboard shows, for the account the user chooses, the incomes and the expenses of each of the last six months. | {account\_id} |
| FR \- 036 | Spending by category | The dashboard shows, for the account the user chooses, how much was spent in the current month on each category. | {account\_id} |

### *Profile Module*

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR \- 037 | Update profile | The signed-in user changes the name and the email. The email can not belong to another user. | name, email |
| FR \- 038 | Profile photo | The user uploads a photo (JPG, PNG or WebP, up to 2 MB), replaces it or removes it. The photo is shown in the user menu, and the initials are shown when there is no photo. | photo |
| FR \- 039 | Delete account | The user deletes the own account, after confirming the password. | password |

### *Security and Feedback Module*

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR \- 040 | Own data only | Every user only sees and changes their own accounts, categories, currencies, incomes, expenses, transfers and profile. Trying to open, change or delete the data of another user is refused, and the forms only offer the user's own data. | \- |
| FR \- 041 | Audit log | Every write (create, update or delete) made by a signed-in user is recorded in an audit log with the user, the action, the result and the address. The content of the forms is never recorded. | \- |
| FR \- 042 | Security headers | Every response carries the standard browser security headers (content type, framing, referrer, permissions and, in production over HTTPS, strict transport security). | \- |
| FR \- 043 | Validation messages | When a form is invalid, every failing field shows, next to it, a message in plain words that names the field and says what to correct. The create and the edit forms of the same model show the same message for the same mistake. | \- |

---
  
