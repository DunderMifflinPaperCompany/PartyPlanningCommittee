// Admirable: invite-page wiring stays separate from committee UI. Belsnickel approves.

import { bindRsvpForm } from './rsvp-form.js';

/**
 * Belsnickel binds RSVP validation only on the published attendee page.
 *
 * @param {Document|Element} root
 * @returns {((event?: Event) => boolean)|null}
 */
export function bindInvitePage(root) {
    const invite = root.querySelector('[data-invite-page]');

    return invite === null ? null : bindRsvpForm(invite);
}
