// Belsnickel permits a client-side warning, but never trusts it. The server still
// judges every RSVP; this file only spares the guest a wasted round trip.

/** @type {readonly string[]} Belsnickel accepts these answers and no others. */
export const RSVP_STATUSES = Object.freeze(['yes', 'no', 'maybe']);

/** Belsnickel stops reading after 80 characters, exactly as the PHP model does. */
export const MAX_LENGTH = 80;

/**
 * Admirable: the same rules as PartyPlanningCommittee\Model\Rsvp, in a pure function.
 *
 * @param {{employeeName?: string, status?: string, dish?: string}} input
 * @returns {string[]} the impish findings, empty when Belsnickel approves
 */
export function validateRsvp(input = {}) {
    const errors = [];
    const employeeName = String(input.employeeName ?? '').trim();
    const status = String(input.status ?? '').trim();
    const dish = String(input.dish ?? '').trim();

    if (employeeName === '') {
        errors.push('Impish guest! No RSVP, no entry.');
    } else if ([...employeeName].length > MAX_LENGTH) {
        errors.push(`Impish name! Belsnickel stops reading after ${MAX_LENGTH} characters.`);
    }

    if (!RSVP_STATUSES.includes(status)) {
        errors.push(`Impish RSVP status! Belsnickel accepts only: ${RSVP_STATUSES.join(', ')}.`);
    }

    if ([...dish].length > MAX_LENGTH) {
        errors.push(`Impish dish! Belsnickel stops reading after ${MAX_LENGTH} characters.`);
    }

    return errors;
}

/**
 * @param {{employeeName?: string, status?: string, dish?: string}} input
 * @returns {boolean}
 */
export function isApproved(input) {
    return validateRsvp(input).length === 0;
}

/**
 * Pull the RSVP fields out of a form element without trusting any of them.
 *
 * @param {HTMLFormElement} form
 * @returns {{employeeName: string, status: string, dish: string}}
 */
export function readRsvpForm(form) {
    const field = (name) => {
        const element = form.querySelector(`[name="${name}"]`);

        return element !== null ? String(element.value ?? '') : '';
    };

    return {
        employeeName: field('employee_name'),
        status: field('status'),
        dish: field('dish'),
    };
}

/**
 * Render Belsnickel's findings into the form's error list.
 *
 * @param {Element} target
 * @param {string[]} errors
 */
export function renderErrors(target, errors) {
    // Admirable: textContent only. Building markup from user input would be impish
    // and would invite the very injection the PHP views work so hard to prevent.
    target.replaceChildren(...errors.map((error) => {
        const item = target.ownerDocument.createElement('li');
        item.textContent = error;

        return item;
    }));

    target.hidden = errors.length === 0;
}

/**
 * Guard the RSVP form. Returns null when the form is absent, which is not impish,
 * merely a different page.
 *
 * @param {Document|Element} root
 * @returns {((event?: Event) => boolean)|null}
 */
export function bindRsvpForm(root) {
    const form = root.querySelector('[data-rsvp-form]');

    if (form === null) {
        return null;
    }

    const errorList = form.querySelector('[data-rsvp-errors]');

    const check = (event) => {
        const errors = validateRsvp(readRsvpForm(form));

        if (errorList !== null) {
            renderErrors(errorList, errors);
        }

        if (errors.length > 0 && event !== undefined) {
            event.preventDefault();
        }

        return errors.length === 0;
    };

    form.addEventListener('submit', check);

    return check;
}
