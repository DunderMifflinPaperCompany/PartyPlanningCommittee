// Belsnickel's entry point. Inline scripts in templates are impish and untestable;
// one module that wires tested modules together is admirable.

import { bindPartyFilter } from './party-filter.js';
import { bindRsvpForm } from './rsvp-form.js';
import { bindCapacityMeter } from './capacity.js';

/**
 * @param {Document|Element} root
 * @returns {{filter: unknown, rsvp: unknown, capacity: unknown}}
 */
export function enhance(root) {
    return {
        filter: bindPartyFilter(root),
        rsvp: bindRsvpForm(root),
        capacity: bindCapacityMeter(root),
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
