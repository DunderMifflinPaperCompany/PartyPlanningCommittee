// Belsnickel tests attendee-page wiring separately, so committee UI cannot trespass here.
import { describe, expect, it } from 'vitest';
import { bindInvitePage } from '../../public/assets/js/invite.js';

function invitePage() {
    document.body.innerHTML = `
        <article data-invite-page>
            <form data-rsvp-form>
                <ul data-rsvp-errors hidden></ul>
                <input name="employee_name" value="Stanley Hudson">
                <select name="status"><option value="yes" selected>yes</option></select>
                <input name="dish" value="Pretzels">
            </form>
        </article>
    `;

    return document;
}

describe('bindInvitePage', () => {
    it('does nothing off the published attendee page', () => {
        document.body.innerHTML = '<p>Committee business is not an invitation.</p>';

        expect(bindInvitePage(document)).toBeNull();
    });

    it('binds the RSVP form on the published attendee page', () => {
        const root = invitePage();
        const check = bindInvitePage(root);

        expect(typeof check).toBe('function');
        expect(check()).toBe(true);

        root.querySelector('[name="employee_name"]').value = '';
        expect(check()).toBe(false);
        expect(root.querySelector('[data-rsvp-errors]').textContent)
            .toBe('Impish guest! No RSVP, no entry.');
    });

    it('does not bind an RSVP form outside the attendee page', () => {
        document.body.innerHTML = `
            <form data-rsvp-form>
                <input name="employee_name" value="">
                <select name="status"><option value="yes" selected>yes</option></select>
                <input name="dish">
            </form>
        `;

        expect(bindInvitePage(document)).toBeNull();
    });
});
