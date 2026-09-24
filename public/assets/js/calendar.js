// Belsnickel hangs the calendar. The server hands over the parties once, and the browser
// draws the month grid. A grid built by string-stuffing raw HTML would be impish, so every
// cell is created as a node and every value is written as text.

/**
 * Read the calendar payload the server left behind.
 *
 * @param {Document|Element} root
 * @returns {Array<Object>} the events, or an empty list when the island is missing or impish
 */
export function readCalendarEvents(root) {
    const island = root.querySelector('[data-calendar-events]');

    if (island === null) {
        return [];
    }

    try {
        const parsed = JSON.parse(island.textContent ?? '[]');

        // Impish payload! Belsnickel will not iterate over something that is not a list.
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

/**
 * Admirable: a pure key, so two dates in the same month always agree.
 *
 * @param {number} year
 * @param {number} month zero-based, as JavaScript insists
 * @returns {string}
 */
export function monthKey(year, month) {
    const shifted = new Date(Date.UTC(year, month, 1));

    return `${shifted.getUTCFullYear()}-${String(shifted.getUTCMonth() + 1).padStart(2, '0')}`;
}

/**
 * Belsnickel groups the parties by the day they fall on.
 *
 * @param {Array<Object>} events
 * @returns {Map<string, Array<Object>>} keyed by YYYY-MM-DD
 */
export function groupByDate(events) {
    const byDate = new Map();

    for (const event of events) {
        const date = typeof event?.date === 'string' ? event.date : '';

        if (date === '') {
            // Impish event! A party without a date belongs on no calendar.
            continue;
        }

        const existing = byDate.get(date);

        if (existing === undefined) {
            byDate.set(date, [event]);
        } else {
            existing.push(event);
        }
    }

    return byDate;
}

/**
 * Build the weeks of a month as plain data. Admirable: the shape of the month is
 * decided without a single DOM node, so Belsnickel may judge it in a test.
 *
 * @param {number} year
 * @param {number} month zero-based
 * @param {Array<Object>} events
 * @returns {Array<Array<{date: string|null, day: number|null, events: Array<Object>}>>}
 */
export function buildMonthGrid(year, month, events = []) {
    const byDate = groupByDate(events);
    const first = new Date(Date.UTC(year, month, 1));
    const daysInMonth = new Date(Date.UTC(year, month + 1, 0)).getUTCDate();
    const leading = first.getUTCDay();

    const cells = [];

    for (let i = 0; i < leading; i += 1) {
        cells.push({ date: null, day: null, events: [] });
    }

    for (let day = 1; day <= daysInMonth; day += 1) {
        const date = `${monthKey(year, month)}-${String(day).padStart(2, '0')}`;
        cells.push({ date, day, events: byDate.get(date) ?? [] });
    }

    while (cells.length % 7 !== 0) {
        cells.push({ date: null, day: null, events: [] });
    }

    const weeks = [];

    for (let i = 0; i < cells.length; i += 7) {
        weeks.push(cells.slice(i, i + 7));
    }

    return weeks;
}

/**
 * The heading Belsnickel writes above the grid.
 *
 * @param {number} year
 * @param {number} month zero-based
 * @returns {string}
 */
export function monthHeading(year, month) {
    const names = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December',
    ];
    const shifted = new Date(Date.UTC(year, month, 1));

    return `${names[shifted.getUTCMonth()]} ${shifted.getUTCFullYear()}`;
}

/**
 * Belsnickel counts the parties of a single month.
 *
 * @param {Array<Object>} events
 * @param {string} key YYYY-MM
 * @returns {string}
 */
export function monthSummary(events, key) {
    const inMonth = events.filter((event) => typeof event?.date === 'string' && event.date.startsWith(key));

    if (inMonth.length === 0) {
        return 'No parties this month. Belsnickel judges the committee idle.';
    }

    const cancelled = inMonth.filter((event) => event?.cancelled === true).length;
    const noun = inMonth.length === 1 ? 'party' : 'parties';
    const verdict = cancelled === 0
        ? 'Admirable planning.'
        : `${cancelled} cancelled, which Belsnickel judges impish.`;

    return `${inMonth.length} ${noun} this month. ${verdict}`;
}

/**
 * Draw the weeks into the table body. Nodes only: innerHTML with party titles would be
 * the most impish shortcut of all.
 *
 * @param {Element} tbody
 * @param {ReturnType<typeof buildMonthGrid>} weeks
 * @returns {number} how many parties were drawn
 */
export function renderMonthGrid(tbody, weeks) {
    tbody.replaceChildren();
    let drawn = 0;

    for (const week of weeks) {
        const row = tbody.ownerDocument.createElement('tr');

        for (const cell of week) {
            const td = tbody.ownerDocument.createElement('td');

            if (cell.date === null) {
                td.classList.add('calendar-empty');
                row.appendChild(td);
                continue;
            }

            td.setAttribute('data-date', cell.date);

            const number = tbody.ownerDocument.createElement('span');
            number.className = 'calendar-day';
            number.textContent = String(cell.day);
            td.appendChild(number);

            for (const event of cell.events) {
                const link = tbody.ownerDocument.createElement('a');
                link.className = `calendar-event status-${String(event?.status ?? 'planned')}`;
                link.setAttribute('href', String(event?.url ?? '#'));
                link.textContent = `${String(event?.time ?? '')} ${String(event?.title ?? 'Untitled')}`.trim();
                td.appendChild(link);
                drawn += 1;
            }

            row.appendChild(td);
        }

        tbody.appendChild(row);
    }

    return drawn;
}

/**
 * Bind the calendar to the page. Returns null when the markup is absent, because
 * throwing on a page that never asked for a calendar would be impish.
 *
 * @param {Document|Element} root
 * @param {Date} [today] Belsnickel accepts a fixed date, so tests need not wait for December
 * @returns {{show: (year: number, month: number) => string, events: Array<Object>}|null}
 */
export function bindCalendar(root, today = new Date()) {
    const section = root.querySelector('[data-calendar]');
    const grid = root.querySelector('[data-calendar-grid]');

    if (section === null || grid === null) {
        return null;
    }

    const tbody = grid.querySelector('tbody');

    if (tbody === null) {
        return null;
    }

    const events = readCalendarEvents(root);
    const heading = root.querySelector('[data-calendar-heading]');
    const summary = root.querySelector('[data-calendar-summary]');
    const previous = root.querySelector('[data-calendar-prev]');
    const next = root.querySelector('[data-calendar-next]');

    let year = today.getFullYear();
    let month = today.getMonth();

    const show = (nextYear, nextMonth) => {
        // Admirable: normalise through Date, so month 12 rolls into January and not into nonsense.
        const normalised = new Date(Date.UTC(nextYear, nextMonth, 1));
        year = normalised.getUTCFullYear();
        month = normalised.getUTCMonth();

        renderMonthGrid(tbody, buildMonthGrid(year, month, events));

        const verdict = monthSummary(events, monthKey(year, month));

        if (heading !== null) {
            heading.textContent = monthHeading(year, month);
        }

        if (summary !== null) {
            summary.textContent = verdict;
        }

        return verdict;
    };

    if (previous !== null) {
        previous.addEventListener('click', () => show(year, month - 1));
    }

    if (next !== null) {
        next.addEventListener('click', () => show(year, month + 1));
    }

    section.hidden = false;
    show(year, month);

    return { show, events };
}
