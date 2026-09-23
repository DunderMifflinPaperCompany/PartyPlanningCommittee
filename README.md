# PartyPlanningCommittee

A small PHP website for tracking Dunder Mifflin Party Planning Committee events across the
Scranton, Utica and Nashua branches. Every line is judged by Belsnickel: impish or admirable.

## What it does

- Lists every party across all branches, with budget, status and confirmed head count.
- Filters parties by branch (`/offices/scranton`, `/offices/utica`, `/offices/nashua`).
- Shows a party page with its guest list, dishes and Belsnickel's verdict.
- Accepts new party proposals, validating branch, title, theme, date, budget and status.
- Publishes a party to its own attendee page (`/parties/{id}/invite`), where guests RSVP and
  sign up to bring refreshments. Unpublished parties stay committee business.
- Records RSVPs (yes, no, maybe). One employee, one verdict: a second RSVP overwrites the first.
- Cancels a party without erasing the evidence.
- Warns when confirmed guests exceed the branch party room capacity.

## Requirements

- PHP 8.2 or newer with `pdo` and `pdo_sqlite`
- Composer (only needed for the test suite)

## Running the site

```bash
composer install          # optional for browsing, required for tests
php -S localhost:8080 -t public
```

Then open <http://localhost:8080>. On first boot the SQLite ledger at `data/committee.sqlite`
is created and seeded with one party per branch. Set the `PPC_DATABASE` environment variable
to store the ledger elsewhere.

## Running the tests

```bash
composer install
vendor/bin/phpunit
```

The suite covers the models, the SQLite repositories (against an in-memory database), the
router, the response helpers, the template renderer, the CSRF guard, the form validator and
the controller end to end.

To measure code coverage locally (requires the Xdebug or PCOV extension), run:

```bash
XDEBUG_MODE=coverage vendor/bin/phpunit
```

This writes a Cobertura XML report to `build/coverage/cobertura.xml`, as configured in
`phpunit.xml`.

## Layout

| Path | Purpose |
| --- | --- |
| `public/index.php` | Front controller: the only way in |
| `src/App.php` | Route table and wiring |
| `src/Model/` | `Office`, `Party`, `Rsvp` value objects, validated on construction |
| `src/Repository/` | SQLite schema, repositories and the first-run seeder |
| `src/Http/` | Router, response, view renderer, CSRF guard, form validator, controller |
| `views/` | PHP templates, every value escaped |
| `tests/` | PHPUnit suite |

## How Belsnickel keeps it honest

- Every SQL statement is prepared. String-concatenated SQL is impish.
- Every template value passes through `View::e()`. Unescaped output earns coal.
- Every state-changing form carries a CSRF token, compared with `hash_equals()`.
- Redirects are reduced to in-app paths, so no open redirect may sneak through.
- Template names are restricted to a tame pattern, so no path traversal is possible.
- Money is stored in integer cents and only formatted at the edges.
- The published page shows the invitation and the refreshment sign-up, never the committee's
  cancel lever, and it refuses to appear until the party is published.
