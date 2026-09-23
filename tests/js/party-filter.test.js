// Belsnickel tests the ledger filter: pure verdicts first, then the DOM that obeys them.
import { describe, expect, it } from 'vitest';
import {
    applyPartyFilter,
    bindPartyFilter,
    filterSummary,
    matchesCriteria,
    readPartyRow,
} from '../../public/assets/js/party-filter.js';

function ledger() {
    document.body.innerHTML = `
        <form data-party-filter hidden>
            <input type="search" data-filter-query>
            <select data-filter-status>
                <option value="all">all</option>
                <option value="planned">planned</option>
                <option value="cancelled">cancelled</option>
            </select>
        </form>
        <p data-filter-summary></p>
        <table data-party-table>
            <tbody>
                <tr data-title="Dundie Awards" data-office="scranton" data-status="planned"></tr>
                <tr data-title="Ice Cream Social" data-office="utica" data-status="cancelled"></tr>
                <tr data-title="Pretzel Day" data-office="scranton" data-status="confirmed"></tr>
            </tbody>
        </table>
    `;

    return document;
}

describe('readPartyRow', () => {
    it('lowercases every attribute so comparisons stay honest', () => {
        const row = document.createElement('tr');
        row.setAttribute('data-title', 'Dundie AWARDS');
        row.setAttribute('data-office', 'Scranton');
        row.setAttribute('data-status', 'Planned');

        expect(readPartyRow(row)).toEqual({
            title: 'dundie awards',
            office: 'scranton',
            status: 'planned',
        });
    });

    it('treats missing attributes as empty rather than throwing', () => {
        expect(readPartyRow(document.createElement('tr'))).toEqual({
            title: '',
            office: '',
            status: '',
        });
    });
});

describe('matchesCriteria', () => {
    const party = { title: 'pretzel day', office: 'scranton', status: 'confirmed' };

    it('keeps every party when no criteria are given', () => {
        expect(matchesCriteria(party)).toBe(true);
        expect(matchesCriteria(party, { query: '   ', status: '' })).toBe(true);
    });

    it('treats "all" as no status filter at all', () => {
        expect(matchesCriteria(party, { status: 'all' })).toBe(true);
    });

    it('rejects a party whose status differs', () => {
        expect(matchesCriteria(party, { status: 'cancelled' })).toBe(false);
    });

    it('matches on title or branch, ignoring case and padding', () => {
        expect(matchesCriteria(party, { query: '  PRETZEL ' })).toBe(true);
        expect(matchesCriteria(party, { query: 'SCRAN' })).toBe(true);
        expect(matchesCriteria(party, { query: 'nashua' })).toBe(false);
    });

    it('requires both the query and the status to agree', () => {
        expect(matchesCriteria(party, { query: 'pretzel', status: 'confirmed' })).toBe(true);
        expect(matchesCriteria(party, { query: 'pretzel', status: 'planned' })).toBe(false);
    });
});

describe('applyPartyFilter', () => {
    it('hides the rejected rows and counts the survivors', () => {
        const root = ledger();
        const rows = root.querySelectorAll('tbody tr');

        expect(applyPartyFilter(rows, { query: 'scranton' })).toBe(2);
        expect(rows[0].hidden).toBe(false);
        expect(rows[1].hidden).toBe(true);
        expect(rows[2].hidden).toBe(false);
    });

    it('unhides rows again when the filter is relaxed', () => {
        const root = ledger();
        const rows = root.querySelectorAll('tbody tr');

        applyPartyFilter(rows, { query: 'nothing at all' });
        expect([...rows].every((row) => row.hidden)).toBe(true);

        expect(applyPartyFilter(rows, {})).toBe(3);
        expect([...rows].some((row) => row.hidden)).toBe(false);
    });
});

describe('filterSummary', () => {
    it('judges an empty result impish', () => {
        expect(filterSummary(0, 3)).toContain('impish');
    });

    it('announces the full ledger', () => {
        expect(filterSummary(3, 3)).toBe('All 3 parties stand for inspection.');
        expect(filterSummary(1, 1)).toBe('All 1 party stands for inspection.');
    });

    it('announces a narrowed ledger', () => {
        expect(filterSummary(1, 3)).toBe('1 of 3 parties remain. Admirable narrowing.');
    });
});

describe('bindPartyFilter', () => {
    it('returns null when the page has no ledger', () => {
        document.body.innerHTML = '<p>Belsnickel is displeased</p>';

        expect(bindPartyFilter(document)).toBeNull();
    });

    it('reveals the controls and summarises the untouched ledger', () => {
        const root = ledger();
        bindPartyFilter(root);

        expect(root.querySelector('[data-party-filter]').hidden).toBe(false);
        expect(root.querySelector('[data-filter-summary]').textContent)
            .toBe('All 3 parties stand for inspection.');
    });

    it('filters live as the guest types', () => {
        const root = ledger();
        bindPartyFilter(root);

        const query = root.querySelector('[data-filter-query]');
        query.value = 'utica';
        query.dispatchEvent(new Event('input', { bubbles: true }));

        const rows = root.querySelectorAll('tbody tr');
        expect(rows[0].hidden).toBe(true);
        expect(rows[1].hidden).toBe(false);
        expect(root.querySelector('[data-filter-summary]').textContent)
            .toBe('1 of 3 parties remain. Admirable narrowing.');
    });

    it('combines the status select with the search box', () => {
        const root = ledger();
        bindPartyFilter(root);

        const status = root.querySelector('[data-filter-status]');
        status.value = 'cancelled';
        status.dispatchEvent(new Event('input', { bubbles: true }));

        expect(root.querySelectorAll('tbody tr')[1].hidden).toBe(false);
        expect(root.querySelector('[data-filter-summary]').textContent)
            .toBe('1 of 3 parties remain. Admirable narrowing.');
    });

    it('never lets the form submit and reload the ledger', () => {
        const root = ledger();
        bindPartyFilter(root);

        const submit = new Event('submit', { bubbles: true, cancelable: true });
        root.querySelector('[data-party-filter]').dispatchEvent(submit);

        expect(submit.defaultPrevented).toBe(true);
    });
});
