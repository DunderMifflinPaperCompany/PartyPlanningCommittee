// Belsnickel checks that the entry point wires every module, and that a bare page
// does not make it throw. A crash on the ledger page would be deeply impish.
import { describe, expect, it } from 'vitest';
import { enhance } from '../../public/assets/js/main.js';

describe('enhance', () => {
    it('binds nothing, and complains about nothing, on an empty page', () => {
        document.body.innerHTML = '<p>Belsnickel is displeased</p>';

        expect(enhance(document)).toEqual({ filter: null, rsvp: null, capacity: null });
    });

    it('wires every enhancement present on a party page', () => {
        document.body.innerHTML = `
            <p data-capacity-meter data-attending="2" data-capacity="10" hidden></p>
            <form data-rsvp-form>
                <ul data-rsvp-errors hidden></ul>
                <input name="employee_name" value="Phyllis">
                <select name="status"><option value="yes" selected>yes</option></select>
                <input name="dish" value="">
            </form>
        `;

        const bound = enhance(document);

        expect(bound.filter).toBeNull();
        expect(typeof bound.rsvp).toBe('function');
        expect(bound.capacity).toBe('8 seats remain. Belsnickel approves, for now.');
    });

    it('wires the ledger filter on the index page', () => {
        document.body.innerHTML = `
            <form data-party-filter hidden><input data-filter-query></form>
            <p data-filter-summary></p>
            <table data-party-table><tbody>
                <tr data-title="Dundies" data-office="scranton" data-status="planned"></tr>
            </tbody></table>
        `;

        const bound = enhance(document);

        expect(typeof bound.filter).toBe('function');
        expect(document.querySelector('[data-filter-summary]').textContent)
            .toBe('All 1 party stands for inspection.');
    });
});
