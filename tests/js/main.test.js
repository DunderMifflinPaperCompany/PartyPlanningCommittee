// Belsnickel checks that the entry point wires every module, and that a bare page
// does not make it throw. A crash on the ledger page would be deeply impish.
import { describe, expect, it } from 'vitest';
import { enhance } from '../../public/assets/js/main.js';

describe('enhance', () => {
    it('binds nothing, and complains about nothing, on an empty page', () => {
        document.body.innerHTML = '<p>Belsnickel is displeased</p>';

        expect(enhance(document)).toEqual({
            filter: null,
            rsvp: null,
            capacity: null,
            invite: null,
            calendar: null,
            calendarExport: null,
            angelaAdmin: null,
        });
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
        expect(bound.invite).toBeNull();
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

    it('wires the calendar grid and its downloads on the calendar page', () => {
        document.body.innerHTML = `
            <script type="application/json" data-calendar-events>[{"id":1,"title":"Dundies","date":"2026-12-18","time":"4:00pm","start":"2026-12-18T16:00:00Z","end":"2026-12-18T18:00:00Z","status":"planned","cancelled":false,"url":"/parties/1"}]</script>
            <section data-calendar hidden>
                <h3 data-calendar-heading></h3>
                <table data-calendar-grid><tbody></tbody></table>
                <p data-calendar-summary></p>
            </section>
            <section data-calendar-export hidden>
                <button type="button" data-calendar-download="ics"></button>
                <p data-calendar-export-summary></p>
            </section>
        `;

        const bound = enhance(document);

        expect(typeof bound.calendar.show).toBe('function');
        expect(bound.calendar.events).toHaveLength(1);
        expect(typeof bound.calendarExport).toBe('function');
        expect(document.querySelector('[data-calendar]').hidden).toBe(false);
        expect(document.querySelector('[data-calendar-export]').hidden).toBe(false);
    });

    it('wires an attendee RSVP only once through the invite module', () => {
        document.body.innerHTML = `
            <article data-invite-page>
                <form data-rsvp-form>
                    <ul data-rsvp-errors hidden></ul>
                    <input name="employee_name" value="Kelly">
                    <select name="status"><option value="yes" selected>yes</option></select>
                    <input name="dish">
                </form>
            </article>
        `;

        const bound = enhance(document);

        expect(bound.rsvp).toBe(bound.invite);
    });

    it('wires Angela’s ledger seal', () => {
        document.body.innerHTML = `
            <section data-angela-admin data-review-mode="strict">
                <p data-angela-ledger-status></p>
                <button type="button" data-angela-ledger-seal>Seal the ledger</button>
            </section>
        `;

        const bound = enhance(document);
        bound.angelaAdmin.click();

        expect(document.querySelector('[data-angela-ledger-status]').textContent).toBe('Ledgersealed.');
        expect(bound.angelaAdmin.textContent).toBe('Sealed by Angela Martin');
        expect(bound.angelaAdmin.disabled).toBe(true);
    });
});
