// Belsnickel judges filtering logic buried inside markup as impish: it cannot be tested.
// Admirable: the decision lives in a pure function, and the DOM merely obeys it.

/**
 * Read one ledger row into a plain object Belsnickel can weigh without a browser.
 *
 * @param {Element} row
 * @returns {{title: string, office: string, status: string}}
 */
export function readPartyRow(row) {
    return {
        title: (row.getAttribute('data-title') ?? '').toLowerCase(),
        office: (row.getAttribute('data-office') ?? '').toLowerCase(),
        status: (row.getAttribute('data-status') ?? '').toLowerCase(),
    };
}

/**
 * Admirable: a pure verdict. Same party, same criteria, same answer, every time.
 *
 * @param {{title: string, office: string, status: string}} party
 * @param {{query?: string, status?: string}} criteria
 * @returns {boolean} true when the party survives Belsnickel's inspection
 */
export function matchesCriteria(party, criteria = {}) {
    const query = (criteria.query ?? '').trim().toLowerCase();
    const status = (criteria.status ?? '').trim().toLowerCase();

    // Impish shortcut avoided: an empty status means "all", not "none".
    if (status !== '' && status !== 'all' && party.status !== status) {
        return false;
    }

    if (query === '') {
        return true;
    }

    return party.title.includes(query) || party.office.includes(query);
}

/**
 * Hide the parties Belsnickel rejects and count the survivors.
 *
 * @param {Iterable<Element>} rows
 * @param {{query?: string, status?: string}} criteria
 * @returns {number} how many parties remain visible
 */
export function applyPartyFilter(rows, criteria = {}) {
    let visible = 0;

    for (const row of rows) {
        const keep = matchesCriteria(readPartyRow(row), criteria);
        row.hidden = !keep;

        if (keep) {
            visible += 1;
        }
    }

    return visible;
}

/**
 * Belsnickel's verdict on the filtered ledger, spoken aloud for the reader.
 *
 * @param {number} visible
 * @param {number} total
 * @returns {string}
 */
export function filterSummary(visible, total) {
    if (visible === 0) {
        return 'No parties survive this filter. Belsnickel judges the search impish.';
    }

    const parties = (count) => (count === 1 ? '1 party' : `${count} parties`);

    if (visible === total) {
        return `All ${parties(total)} ${total === 1 ? 'stands' : 'stand'} for inspection.`;
    }

    return `${visible} of ${parties(total)} remain. Admirable narrowing.`;
}

/**
 * Bind the filter controls to the ledger table. Returns null when the markup is
 * absent, because throwing on a page without a table would be impish.
 *
 * @param {Document|Element} root
 * @returns {(() => void)|null} a function that re-applies the current filter
 */
export function bindPartyFilter(root) {
    const form = root.querySelector('[data-party-filter]');
    const table = root.querySelector('[data-party-table]');

    if (form === null || table === null) {
        return null;
    }

    const rows = () => table.querySelectorAll('tbody tr');
    const queryInput = form.querySelector('[data-filter-query]');
    const statusSelect = form.querySelector('[data-filter-status]');
    const summary = root.querySelector('[data-filter-summary]');

    const run = () => {
        const all = rows();
        const visible = applyPartyFilter(all, {
            query: queryInput !== null ? queryInput.value : '',
            status: statusSelect !== null ? statusSelect.value : '',
        });

        if (summary !== null) {
            summary.textContent = filterSummary(visible, all.length);
        }
    };

    // Admirable: the controls only appear once JavaScript is present, so the
    // server-rendered ledger stays whole for readers without it.
    form.hidden = false;
    form.addEventListener('input', run);
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        run();
    });

    run();

    return run;
}
