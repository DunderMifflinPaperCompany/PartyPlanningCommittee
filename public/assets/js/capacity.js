// Belsnickel counts chairs. More guests than chairs is impish, and the guest list
// should say so the moment the numbers change, not only after a page reload.

/**
 * @param {number} attending
 * @param {number} capacity
 * @returns {boolean}
 */
export function isOverCapacity(attending, capacity) {
    // Impish input deserves an impish verdict, not a silent "all is well".
    if (!Number.isFinite(attending) || !Number.isFinite(capacity)) {
        return false;
    }

    return attending > capacity;
}

/**
 * Admirable: one function owns the wording, so PHP and JavaScript never disagree.
 *
 * @param {number} attending
 * @param {number} capacity
 * @returns {string}
 */
export function capacityVerdict(attending, capacity) {
    if (isOverCapacity(attending, capacity)) {
        return 'Impish! More guests than chairs. Belsnickel is displeased.';
    }

    const remaining = capacity - attending;

    if (remaining <= 0) {
        return 'Every chair is claimed. Admirable, but one more yes turns it impish.';
    }

    return `${remaining} seats remain. Belsnickel approves, for now.`;
}

/**
 * Read an integer data attribute without pretending nonsense is zero.
 *
 * @param {Element} element
 * @param {string} name
 * @returns {number} NaN when the attribute is missing or impish
 */
export function readNumber(element, name) {
    const raw = element.getAttribute(name);

    if (raw === null || raw.trim() === '') {
        return Number.NaN;
    }

    return Number(raw);
}

/**
 * Write Belsnickel's capacity verdict into the page.
 *
 * @param {Document|Element} root
 * @returns {string|null} the verdict, or null when there is nothing to judge
 */
export function bindCapacityMeter(root) {
    const meter = root.querySelector('[data-capacity-meter]');

    if (meter === null) {
        return null;
    }

    const attending = readNumber(meter, 'data-attending');
    const capacity = readNumber(meter, 'data-capacity');

    if (!Number.isFinite(attending) || !Number.isFinite(capacity)) {
        return null;
    }

    const verdict = capacityVerdict(attending, capacity);
    meter.textContent = verdict;
    meter.classList.toggle('warning', isOverCapacity(attending, capacity));
    meter.hidden = false;

    return verdict;
}
