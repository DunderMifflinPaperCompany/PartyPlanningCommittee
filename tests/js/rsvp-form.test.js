// Belsnickel checks that the browser rules match the PHP rules exactly. Drift would be impish.
import { describe, expect, it } from 'vitest';
import {
    RSVP_STATUSES,
    bindRsvpForm,
    isApproved,
    readRsvpForm,
    renderErrors,
    validateRsvp,
} from '../../public/assets/js/rsvp-form.js';

function rsvpPage() {
    document.body.innerHTML = `
        <form data-rsvp-form>
            <ul data-rsvp-errors hidden></ul>
            <input name="employee_name" value="Kevin Malone">
            <select name="status">
                <option value="yes" selected>yes</option>
                <option value="no">no</option>
            </select>
            <input name="dish" value="Famous chili">
            <button type="submit">Submit RSVP</button>
        </form>
    `;

    return document;
}

describe('validateRsvp', () => {
    it('approves a complete RSVP', () => {
        expect(validateRsvp({ employeeName: 'Pam Beesly', status: 'yes', dish: 'Pretzels' })).toEqual([]);
        expect(isApproved({ employeeName: 'Pam Beesly', status: 'maybe' })).toBe(true);
    });

    it('refuses a nameless guest: no RSVP, no entry', () => {
        expect(validateRsvp({ status: 'yes' })).toContain('Impish guest! No RSVP, no entry.');
        expect(validateRsvp({ employeeName: '   ', status: 'yes' })).toHaveLength(1);
    });

    it('stops reading names after 80 characters', () => {
        expect(validateRsvp({ employeeName: 'a'.repeat(80), status: 'yes' })).toEqual([]);
        expect(validateRsvp({ employeeName: 'a'.repeat(81), status: 'yes' }))
            .toContain('Impish name! Belsnickel stops reading after 80 characters.');
    });

    it('counts characters, not bytes, so multibyte names are judged fairly', () => {
        expect(validateRsvp({ employeeName: 'é'.repeat(80), status: 'yes' })).toEqual([]);
        expect(validateRsvp({ employeeName: 'é'.repeat(81), status: 'yes' })).toHaveLength(1);
    });

    it('accepts only yes, no and maybe', () => {
        expect(RSVP_STATUSES).toEqual(['yes', 'no', 'maybe']);

        for (const status of RSVP_STATUSES) {
            expect(validateRsvp({ employeeName: 'Oscar', status })).toEqual([]);
        }

        expect(validateRsvp({ employeeName: 'Oscar', status: 'perhaps' }))
            .toContain('Impish RSVP status! Belsnickel accepts only: yes, no, maybe.');
    });

    it('stops reading dishes after 80 characters', () => {
        expect(validateRsvp({ employeeName: 'Angela', status: 'no', dish: 'x'.repeat(81) }))
            .toContain('Impish dish! Belsnickel stops reading after 80 characters.');
    });

    it('reports every impish finding at once', () => {
        expect(validateRsvp({ employeeName: '', status: 'nope', dish: 'x'.repeat(81) })).toHaveLength(3);
    });

    it('survives being handed nothing at all', () => {
        expect(validateRsvp()).toHaveLength(2);
    });
});

describe('readRsvpForm', () => {
    it('reads the named fields and ignores the rest', () => {
        const root = rsvpPage();

        expect(readRsvpForm(root.querySelector('form'))).toEqual({
            employeeName: 'Kevin Malone',
            status: 'yes',
            dish: 'Famous chili',
        });
    });

    it('returns empty strings for fields that are absent', () => {
        document.body.innerHTML = '<form><input name="employee_name" value="Toby"></form>';

        expect(readRsvpForm(document.querySelector('form'))).toEqual({
            employeeName: 'Toby',
            status: '',
            dish: '',
        });
    });
});

describe('renderErrors', () => {
    it('writes findings as text, never as markup', () => {
        document.body.innerHTML = '<ul data-rsvp-errors hidden></ul>';
        const list = document.querySelector('[data-rsvp-errors]');

        renderErrors(list, ['<img src=x onerror="alert(1)">']);

        expect(list.hidden).toBe(false);
        expect(list.querySelectorAll('li')).toHaveLength(1);
        expect(list.querySelector('img')).toBeNull();
        expect(list.textContent).toBe('<img src=x onerror="alert(1)">');
    });

    it('hides itself again once Belsnickel is satisfied', () => {
        document.body.innerHTML = '<ul data-rsvp-errors></ul>';
        const list = document.querySelector('[data-rsvp-errors]');

        renderErrors(list, ['Impish guest! No RSVP, no entry.']);
        renderErrors(list, []);

        expect(list.hidden).toBe(true);
        expect(list.children).toHaveLength(0);
    });
});

describe('bindRsvpForm', () => {
    it('returns null on pages without an RSVP form', () => {
        document.body.innerHTML = '<p>Nothing here.</p>';

        expect(bindRsvpForm(document)).toBeNull();
    });

    it('lets an admirable RSVP through to the server', () => {
        const root = rsvpPage();
        bindRsvpForm(root);

        const submit = new Event('submit', { bubbles: true, cancelable: true });
        root.querySelector('form').dispatchEvent(submit);

        expect(submit.defaultPrevented).toBe(false);
        expect(root.querySelector('[data-rsvp-errors]').hidden).toBe(true);
    });

    it('blocks an impish RSVP and shows the findings', () => {
        const root = rsvpPage();
        bindRsvpForm(root);
        root.querySelector('[name="employee_name"]').value = '  ';

        const submit = new Event('submit', { bubbles: true, cancelable: true });
        root.querySelector('form').dispatchEvent(submit);

        expect(submit.defaultPrevented).toBe(true);
        expect(root.querySelector('[data-rsvp-errors]').textContent)
            .toBe('Impish guest! No RSVP, no entry.');
    });

    it('can be asked for a verdict without an event', () => {
        const root = rsvpPage();
        const check = bindRsvpForm(root);

        expect(check()).toBe(true);

        root.querySelector('[name="dish"]').value = 'x'.repeat(81);
        expect(check()).toBe(false);
    });
});
