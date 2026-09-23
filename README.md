# PartyPlanningCommittee

A small PHP website for tracking Dunder Mifflin Party Planning Committee events across the
Scranton, Utica and Nashua branches. Every line is judged by Belsnickel: impish or admirable.

## What it does

- Lists every party across all branches, with budget, status and confirmed head count.
- Filters parties by branch (`/offices/scranton`, `/offices/utica`, `/offices/nashua`).
- Shows a party page with its guest list, dishes and Belsnickel's verdict.
- Accepts new party proposals, validating branch, title, theme, date, budget and status.
- Records RSVPs (yes, no, maybe). One employee, one verdict: a second RSVP overwrites the first.
- Cancels a party without erasing the evidence.
- Warns when confirmed guests exceed the branch party room capacity.

## Requirements

- PHP 8.2 or newer with `pdo` and `pdo_sqlite`
- Composer (only needed for the PHP test suite)
- Node.js 20 or newer (only needed for the JavaScript test suite)

## Running the site

```bash
composer install          # optional for browsing, required for tests
php -S localhost:8080 -t public
```

Then open <http://localhost:8080>. On first boot the SQLite ledger at `data/committee.sqlite`
is created and seeded with one party per branch. Set the `PPC_DATABASE` environment variable
to store the ledger elsewhere.

## Running the tests

Two suites judge this site: PHPUnit for the server, Vitest for the browser.

```bash
composer install
vendor/bin/phpunit          # the PHP suite

npm install
npm test                    # the JavaScript suite
```

The PHP suite covers the models, the SQLite repositories (against an in-memory database), the
router, the response helpers, the template renderer, the CSRF guard, the form validator and
the controller end to end.

To measure code coverage locally (requires the Xdebug or PCOV extension), run:

```bash
XDEBUG_MODE=coverage vendor/bin/phpunit
```

This writes a Cobertura XML report to `build/coverage/cobertura.xml`, as configured in
`phpunit.xml`.

The JavaScript suite runs under [Vitest](https://vitest.dev/) with a jsdom DOM, so the
front-end modules are exercised without a browser:

```bash
npm run test:coverage
```

That writes its own Cobertura report to `build/coverage-js/cobertura-coverage.xml`, as
configured in `vitest.config.js`. Both suites run on every push in
`.github/workflows/tests.yml`.

## Front-end code

There is no inline JavaScript in the templates. Behaviour lives in ES modules under
`public/assets/js/`, loaded once from the layout as `<script type="module">`:

| Module | Purpose |
| --- | --- |
| `party-filter.js` | Filters the ledger table by search text and status |
| `rsvp-form.js` | Mirrors the RSVP rules in the browser, before the server re-checks them |
| `capacity.js` | Turns the attending and capacity numbers into Belsnickel's verdict |
| `main.js` | The single entry point that wires the modules to the page |

Each module splits pure decision functions from the thin DOM bindings that call them, and
the templates communicate through `data-` attributes only. Every enhancement degrades
quietly: with JavaScript off, the server-rendered pages still work, and the filter controls
stay hidden.

These are progressive enhancements, never a security boundary. The server still validates
every RSVP, checks every CSRF token and escapes every value.

## Layout

| Path | Purpose |
| --- | --- |
| `public/index.php` | Front controller: the only way in |
| `src/App.php` | Route table and wiring |
| `src/Model/` | `Office`, `Party`, `Rsvp` value objects, validated on construction |
| `src/Repository/` | SQLite schema, repositories and the first-run seeder |
| `src/Http/` | Router, response, view renderer, CSRF guard, form validator, controller |
| `views/` | PHP templates, every value escaped |
| `public/assets/js/` | External, testable front-end ES modules |
| `tests/` | PHPUnit suite |
| `tests/js/` | Vitest suite for the front-end modules |

## How Belsnickel keeps it honest

- Every SQL statement is prepared. String-concatenated SQL is impish.
- Every template value passes through `View::e()`. Unescaped output earns coal.
- Every state-changing form carries a CSRF token, compared with `hash_equals()`.
- Redirects are reduced to in-app paths, so no open redirect may sneak through.
- Template names are restricted to a tame pattern, so no path traversal is possible.
- Money is stored in integer cents and only formatted at the edges.
