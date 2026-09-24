// Belsnickel lets the guests carry the calendar home, but the file is written here, in the
// browser, from the values the server already judged. Three formats are permitted: ICS for
// calendar programs, CSV for spreadsheets, JSON for the curious.

const PRODUCT_ID = '-//Dunder Mifflin Party Planning Committee//Belsnickel//EN';

/**
 * Turn an ISO instant into the stamp calendar programs demand.
 *
 * @param {string} iso
 * @returns {string} e.g. 20261218T160000Z, or '' when the input is impish
 */
export function toIcsStamp(iso) {
    const moment = new Date(String(iso ?? ''));

    if (Number.isNaN(moment.getTime())) {
        // Impish date! Belsnickel refuses to invent a moment that was never given.
        return '';
    }

    return moment.toISOString().replace(/[-:]/g, '').replace(/\.\d{3}Z$/, 'Z');
}

/**
 * Escape a value for an ICS text field. Unescaped commas and newlines corrupt the whole
 * file, which is impish beyond forgiveness.
 *
 * @param {string} value
 * @returns {string}
 */
export function escapeIcsText(value) {
    return String(value ?? '')
        .replace(/\\/g, '\\\\')
        .replace(/\r\n|\n|\r/g, '\\n')
        .replace(/;/g, '\\;')
        .replace(/,/g, '\\,');
}

/**
 * Fold a content line to 75 octets, as RFC 5545 demands.
 *
 * @param {string} line
 * @returns {string}
 */
export function foldIcsLine(line) {
    const text = String(line ?? '');

    if (text.length <= 75) {
        return text;
    }

    const parts = [text.slice(0, 75)];

    for (let i = 75; i < text.length; i += 74) {
        parts.push(` ${text.slice(i, i + 74)}`);
    }

    return parts.join('\r\n');
}

/**
 * Belsnickel translates a party's status for the calendar program.
 *
 * @param {Object} event
 * @returns {string}
 */
export function icsStatus(event) {
    if (event?.cancelled === true || event?.status === 'cancelled') {
        return 'CANCELLED';
    }

    return event?.status === 'confirmed' ? 'CONFIRMED' : 'TENTATIVE';
}

/**
 * Build an RFC 5545 calendar from the events.
 *
 * @param {Array<Object>} events
 * @param {{stamp?: string, origin?: string}} [options]
 * @returns {string}
 */
export function toIcs(events, options = {}) {
    const stamp = toIcsStamp(options.stamp ?? new Date().toISOString());
    const origin = String(options.origin ?? '');
    const lines = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        `PRODID:${PRODUCT_ID}`,
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH',
        'X-WR-CALNAME:Dunder Mifflin Party Planning Committee',
    ];

    for (const event of events ?? []) {
        const start = toIcsStamp(event?.start);

        if (start === '') {
            // Impish event! A party with no start is left off the file rather than corrupting it.
            continue;
        }

        const description = [
            event?.theme ? `Theme: ${event.theme}` : '',
            event?.organizer ? `Organiser: ${event.organizer}` : '',
            event?.budget ? `Budget: ${event.budget}` : '',
            'Judged by Belsnickel: impish or admirable.',
        ].filter((part) => part !== '').join('\n');

        lines.push(
            'BEGIN:VEVENT',
            `UID:party-${String(event?.id ?? start)}@dundermifflin.invalid`,
            `DTSTAMP:${stamp}`,
            `DTSTART:${start}`,
            `DTEND:${toIcsStamp(event?.end) || start}`,
            `SUMMARY:${escapeIcsText(event?.title ?? 'Untitled party')}`,
            `DESCRIPTION:${escapeIcsText(description)}`,
            `LOCATION:${escapeIcsText(event?.location ?? '')}`,
            `STATUS:${icsStatus(event)}`,
        );

        if (origin !== '' && typeof event?.url === 'string') {
            lines.push(`URL:${escapeIcsText(origin + event.url)}`);
        }

        lines.push('END:VEVENT');
    }

    lines.push('END:VCALENDAR');

    return `${lines.map(foldIcsLine).join('\r\n')}\r\n`;
}

/**
 * Neutralise a cell a spreadsheet might otherwise execute. Belsnickel judges formula
 * injection the most impish trick a party title has ever attempted.
 *
 * @param {unknown} value
 * @returns {string}
 */
export function escapeCsvCell(value) {
    let text = value === null || value === undefined ? '' : String(value);

    if (/^[=+\-@\t\r]/.test(text)) {
        text = `'${text}`;
    }

    return `"${text.replace(/"/g, '""')}"`;
}

/**
 * Build a spreadsheet of the events.
 *
 * @param {Array<Object>} events
 * @returns {string}
 */
export function toCsv(events) {
    const columns = ['title', 'date', 'time', 'start', 'end', 'location', 'organizer', 'status', 'theme', 'budget', 'attending'];
    const rows = [columns.map(escapeCsvCell).join(',')];

    for (const event of events ?? []) {
        rows.push(columns.map((column) => escapeCsvCell(event?.[column])).join(','));
    }

    return `${rows.join('\r\n')}\r\n`;
}

/**
 * Build the plain JSON export. Admirable: the same events, no reshaping, no surprises.
 *
 * @param {Array<Object>} events
 * @returns {string}
 */
export function toJson(events) {
    return `${JSON.stringify(events ?? [], null, 2)}\n`;
}

/**
 * The three formats Belsnickel permits, and how each is written.
 */
export const FORMATS = {
    ics: { mime: 'text/calendar;charset=utf-8', extension: 'ics', build: toIcs },
    csv: { mime: 'text/csv;charset=utf-8', extension: 'csv', build: toCsv },
    json: { mime: 'application/json;charset=utf-8', extension: 'json', build: toJson },
};

/**
 * @param {string} format
 * @returns {string}
 */
export function filenameFor(format) {
    const known = FORMATS[format];

    return `dunder-mifflin-parties.${known ? known.extension : 'txt'}`;
}

/**
 * Build the export for one format.
 *
 * @param {Array<Object>} events
 * @param {string} format
 * @param {{stamp?: string, origin?: string}} [options]
 * @returns {{filename: string, mime: string, contents: string}|null} null for a format Belsnickel does not know
 */
export function buildExport(events, format, options = {}) {
    const known = FORMATS[format];

    if (known === undefined) {
        // Impish request! Belsnickel writes only the formats he named.
        return null;
    }

    return {
        filename: filenameFor(format),
        mime: known.mime,
        contents: known.build(events, options),
    };
}

/**
 * Hand the file to the browser. Belsnickel revokes the object URL afterwards: a leaked
 * blob is impish housekeeping.
 *
 * @param {Document} doc
 * @param {{filename: string, mime: string, contents: string}} file
 * @returns {boolean} true when the download was offered
 */
export function downloadFile(doc, file) {
    const view = doc.defaultView;

    if (view === null || typeof view.Blob !== 'function' || typeof view.URL?.createObjectURL !== 'function') {
        return false;
    }

    const url = view.URL.createObjectURL(new view.Blob([file.contents], { type: file.mime }));
    const link = doc.createElement('a');
    link.href = url;
    link.download = file.filename;
    doc.body.appendChild(link);
    link.click();
    link.remove();
    view.URL.revokeObjectURL(url);

    return true;
}

/**
 * Belsnickel's verdict on a completed download.
 *
 * @param {string} format
 * @param {number} count
 * @returns {string}
 */
export function exportSummary(format, count) {
    if (count === 0) {
        return 'Nothing to take home. Belsnickel judges an empty calendar impish.';
    }

    const noun = count === 1 ? 'party' : 'parties';

    return `${count} ${noun} written to ${filenameFor(format)}. Admirable, but Belsnickel is still watching.`;
}

/**
 * Bind the download buttons. Returns null when no export section is present.
 *
 * @param {Document|Element} root
 * @param {Array<Object>} events
 * @returns {((format: string) => string|null)|null}
 */
export function bindCalendarExport(root, events = []) {
    const section = root.querySelector('[data-calendar-export]');

    if (section === null) {
        return null;
    }

    const doc = root.ownerDocument ?? root;
    const summary = root.querySelector('[data-calendar-export-summary]');
    const origin = doc.defaultView?.location?.origin ?? '';

    const run = (format) => {
        const file = buildExport(events, format, { origin });

        if (file === null) {
            return null;
        }

        downloadFile(doc, file);
        const verdict = exportSummary(format, events.length);

        if (summary !== null) {
            summary.textContent = verdict;
        }

        return verdict;
    };

    for (const button of section.querySelectorAll('[data-calendar-download]')) {
        button.addEventListener('click', () => run(button.getAttribute('data-calendar-download') ?? ''));
    }

    // Admirable: the buttons appear only once they are wired to something that works.
    section.hidden = false;

    return run;
}
