---
name: "writing-development-tickets"
description: "Scope and file one well-formed GitHub issue for Saldo. Use when the user says 'let's open an issue for X', 'file a ticket', 'write up the Y work', or wants to draft the batch of issues that follows a milestone plan. Produces a single issue via `gh issue create` following the repo's feature/bug template, with the right type label and an atomic, one-commit-per-step progress list."
---

# Writing Development Tickets

This skill turns a rough idea into a single GitHub issue that the executing pipeline can pick up without follow-up questions. One issue, one intent, one "done".

## When to invoke

- User says "let's write an issue / ticket for X".
- User is producing a batch of issues to seed a milestone.
- Planning surfaced a follow-up that should be tracked separately.

Do NOT use this to drive execution — that's `/executing-development-tickets`.

## Non-negotiable shape

Every issue produced by this skill has:

1. **A single intent.** If "and" appears in the title, split it.
2. **A defined done.** Acceptance criteria in bullet form, each independently verifiable.
3. **Explicit out-of-scope.** What this ticket will *not* do. Prevents scope creep during execution.
4. **A link to context.** The relevant section of the product context doc, the relevant ADR, or the parent issue.
5. **A label + milestone.** Always a label (see the label table in step 4); a milestone when the user names one.
6. **A steps section.** An ordered list of atomic steps (see "Writing the steps" below).

## Writing the steps

Every ticket ends with a `## Progress steps` section, always, for features and bugs alike. It lets the user see they are moving towards the goal.

Each step must be:

- **One commit.** If it needs "and" to describe, split it.
- **Small.** A step touches roughly 5–10 files at most, tests included. It does not have to be a single file. If a step would exceed 10 files, split it.
- **Self-contained.** It includes its own tests and leaves the app working and the test suite green.
- **A visible checkpoint.** Phrased as the outcome reached (what now works), not as a file or class to edit.
- **Ordered.** Each step builds only on earlier ones.

Keep the list short: usually 3–8 steps. If a ticket needs more than ~8 steps, the ticket is probably two intents; propose the split. Steps name checkpoints, not the solution: the "no implementation details" rule still applies.

Format: a numbered list, each item `**Short name:** the outcome after this commit.`

## Workflow

### 1. Understand the intent

Ask *only* if you cannot answer these from the conversation:

- What user-visible or system-visible behaviour will exist after this ticket that doesn't exist today?
- Which module or product area does this belong to?
- What type is it: a brand-new capability, an improvement to an existing one, a bug, a refactor, documentation or design work? (See the label table in step 4. Chores with no fitting label get no type label.)
- Are there upstream tickets that must land first?

If the user's ask spans multiple intents, name the split explicitly: "I'll file three issues — <A>, <B>, <C>. Confirm?" Do not silently narrow.

### 2. Read the relevant context

Before drafting the body: read the relevant product context doc, any ADRs in `docs/adr/` that touch this area, and any related open issues (`gh issue list --search "<keywords>"`). The ticket body cites what's authoritative.

### 3. Draft the ticket

Two templates — pick the one that matches:

#### Feature ticket

```markdown
## Problem

<1–3 sentences — what limitation exists today, or what user need is unmet>

## Motivation

<Why this matters now. Link to the product spec section or the ADR / parent issue.>

## Proposed outcome

<What exists after this ticket ships. User-visible behaviour first, then the surface (API endpoint, schema field, bot command). No implementation details — those come from the planner.>

## Acceptance criteria

- [ ] <criterion 1 — independently verifiable>
- [ ] <criterion 2>
- [ ] <criterion 3>

## Out of scope

- <thing this ticket will not do — one bullet per>

## Progress steps

Each step is one commit (about 5–10 files at most, with its tests) and leaves the app working.

1. **<Short name>:** <outcome after this commit>
2. **<Short name>:** <outcome after this commit>
3. **<Short name>:** <outcome after this commit>

## Notes

<Optional: known constraints, related tickets, hints for the planner. Never a solution sketch — that's the planner's job.>
```

#### Bug ticket

```markdown
## Observed

<What happens today — concrete inputs → wrong output or crash. Include the wire payload / stack trace / screenshot if you have one.>

## Expected

<What should happen instead. Cite the spec section that establishes the expectation.>

## Reproduction

1. <step>
2. <step>
3. <observed>

## Acceptance criteria

- [ ] A regression test exists that fails on the current `main` and passes after the fix.
- [ ] <any user-visible fix criterion>

## Out of scope

- <adjacent problems this ticket will not fix>

## Progress steps

Each step is one commit (about 5–10 files at most, with its tests) and leaves the app working.

1. **Regression test:** a failing test reproduces the bug (or ships together with the fix if the suite must stay green).
2. **<Short name>:** <outcome after this commit>
```

### 4. File the issue

Show the drafted body to the user and confirm before filing. Then:

```bash
gh issue create \
  --title "<type>: <slug>" \
  --body-file <path/to/drafted-body.md> \
  --label "<label>" \
  --milestone "<milestone or omit>"
```

Pick exactly one label from the repo's type labels:

| Kind of work | Label | Title prefix |
|---|---|---|
| Brand-new capability | `new feature` | `feat` |
| Improvement to an existing capability | `enhancement` | `feat` |
| Something is broken | `bug` | `fix` |
| Code change, no user-visible behaviour change | `refactor` | `refactor` |
| Docs only | `documentation` | `docs` |
| UI/UX or visual design work | `design` | `design` |

Chores with no fitting row get no type label and the `chore` prefix. Other labels (`accessibility`, `good first issue`, …) may be added on top when relevant. If the repo's labels have changed, check with `gh label list`.

Title format: `<prefix>: <short imperative>` — e.g. `feat: add /start command to Telegram bot`, `fix: /help crashes on empty message`. Under 72 chars.

After filing, return the issue number and URL to the user.

## Batch mode

When the user is seeding a milestone with several issues:

1. Draft *all* of them first as a numbered list — do not file yet.
2. Present the batch: title + one-line summary each.
3. Ask the user to confirm the batch, edit any titles, and name the milestone.
4. File them in order, capturing each issue number, so later issues in the batch can reference earlier ones ("depends on #NN").

## Anti-patterns

- **Solution in the ticket body.** The ticket says *what* and *why*, never *how*. Implementation is the planner's job.
- **"Investigate X".** Not a ticket. Either it's a bug (write a bug ticket) or a chore ("document current behaviour of X").
- **Multi-intent titles** (`feat: add command and update schema and refactor router`). Split.
- **Missing "out of scope".** Without it, execution creeps. Always include, even if it's "None."
- **Missing or oversized steps.** Every ticket has a steps section. A step that spans more than one commit, or more than ~10 files, must be split.
- **Copying context doc verbatim.** Link to the section; don't restate it.
