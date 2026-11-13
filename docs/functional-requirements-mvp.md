# Requirement Engineering

This section aims to provide a clear understanding of the project's scope and specifications. It describes the intended users and lists the functional and non-functional requirements, demonstrating how the final product effectively addresses these needs.

## Functional Requirements

The functional requirements define all the logic business actions that the application/system is able to perform, with or without user interaction. The functional requirements are going to be divided into **functional modules**, in order to separate them depending on the domain they are working on.

### *Authentication Module*

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR \- 001 | Register | The user registers a new user account in the system. Duplicated emails should be checked. | name, email, password, birth\_date |
| FR \- 002 | Login | The user joins into the system with his credentials.  | email, password |
| FR \- 003 | Logout | The user should be able to logout from the system | \- |

### *Accounts Module*

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR \- 004 | Create | The user registers a new bank account in the system. | name, bank, details, min\_treshold |
| FR \- 005  | Update | The user updates one bank account already created.  | {account\_id}, name, bank, details, min\_treshold |
| FR \- 006 | Read | The user sees all accounts and details of one specific account. | {account\_id} for one account. None for all. |
| FR \- 007 | Delete | The user deactivates one bank account. | {account\_id} |

### *Expenses Module*

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR \- 008 | Create | The user creates a new expense, and it automatically deducts the balance from the account. | amount, concept, date, category\_id |
| FR \- 009  | Update | The user updates one expense, but it’s only able to edit its details. | {expense\_id}, concept |
| FR \- 010 | Read | The user sees all the expenses, and should be able to filter by account, and read full content of one specific expense. | {expense\_id} for one specific expense. None for every. |

### *Incomes Module*

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR \- 011 | Create | The user creates a new income, and it automatically increments the balance from the account. | amount, concept, date, category\_id |
| FR \- 012  | Update | The user updates one income, but it’s only able to edit its details. | {income\_id}, concept |
| FR \- 013 | Read | The user sees all the income, and should be able to filter by account, and read full content of one specific income. | {income\_id} for one specific expense. None for every. |

### *Categories Module*

| Code | Name | Description | Input |
| ----- | ----- | ----- | ----- |
| FR \- 014 | Create | The user registers a new category. | name, details |
| FR \- 015  | Update | The user updates one category. | {category\_id}, name, details |
| FR \- 016 | Read | The user sees all categories and data for specific ones. | {category\_id} for one category. None for all. |
| FR \- 017 | Delete | The user deletes one category. Note: It can not be deleted if the category has been already associated with an expense/income. | {category\_id} |

