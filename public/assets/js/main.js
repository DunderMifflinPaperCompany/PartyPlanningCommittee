// Belsnickel's entry point. Inline scripts in templates are impish and untestable;
// one module that wires tested modules together is admirable.

import { bindPartyFilter } from './party-filter.js';
import { bindRsvpForm } from './rsvp-form.js';
import { bindCapacityMeter } from './capacity.js';
import { bindInvitePage } from './invite.js';
import { bindCalendar, readCalendarEvents } from './calendar.js';
import { bindCalendarExport } from './calendar-export.js';
import { bindAngelaAdmin } from './angela-admin.js';
import { bindMusicRequests } from './music-requests.js';

/**
 * @param {Document|Element} root
 * @returns {{filter: unknown, rsvp: unknown, capacity: unknown, invite: unknown, calendar: unknown, calendarExport: unknown, angelaAdmin: unknown, musicRequests: unknown}}
 */
export function enhance(root) {
    const invite = bindInvitePage(root);
    const calendar = bindCalendar(root);

    return {
        filter: bindPartyFilter(root),
        rsvp: invite ?? bindRsvpForm(root),
        capacity: bindCapacityMeter(root),
        invite,
        calendar,
        // Admirable: the downloads are fed the very events the grid drew, never a second reading.
        calendarExport: bindCalendarExport(root, calendar?.events ?? readCalendarEvents(root)),
        angelaAdmin: bindAngelaAdmin(root),
        musicRequests: bindMusicRequests(root),
    };
}

/* c8 ignore start -- Belsnickel judges browser bootstrapping by hand, not by coverage. */
if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => enhance(document));
    } else {
        enhance(document);
    }
}
/* c8 ignore stop */
