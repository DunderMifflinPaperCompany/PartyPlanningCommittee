// Belsnickel judges the exports. A calendar file that a program cannot read, or a
// spreadsheet cell that runs a formula, is impish and earns far more than coal.
import { describe, expect, it, vi } from 'vitest';
import {
    bindCalendarExport,
    buildExport,
    escapeCsvCell,
    escapeIcsText,
    exportSummary,
    filenameFor,
    foldIcsLine,
    icsStatus,
    toCsv,
    toIcs,
    toIcsStamp,
    toJson,
} from '../../public/assets/js/calendar-export.js';

const christmas = {
    id: 1,
    title: 'Christmas Party',
    theme: 'Belsnickel judgment',
    location: 'Scranton, PA',
    organizer: 'Angela Martin',
    status: 'planned',
    cancelled: false,
    budget: '$250.00',
    attending: 12,
    url: '/parties/1',
    date: '2026-12-18',
    time: '4:00pm',
    start: '2026-12-18T16:00:00Z',
    end: '2026-12-18T18:00:00Z',
};

describe('toIcsStamp', () => {
    it('writes the stamp calendar programs demand', () => {
        expect(toIcsStamp('2026-12-18T16:00:00Z')).toBe('20261218T160000Z');
    });

    it('refuses to invent a moment from impish input', () => {
        expect(toIcsStamp('not a date')).toBe('');
        expect(toIcsStamp(undefined)).toBe('');
    });
});

describe('escapeIcsText', () => {
    it('escapes the characters that would corrupt the file', () => {
        expect(escapeIcsText('Cookies, milk; and\nBelsnickel\\'))
            .toBe('Cookies\\, milk\\; and\\nBelsnickel\\\\');
    });
});

describe('foldIcsLine', () => {
    it('leaves a short line alone', () => {
        expect(foldIcsLine('SUMMARY:Christmas Party')).toBe('SUMMARY:Christmas Party');
    });

    it('folds a long line with a leading space, as RFC 5545 demands', () => {
        const folded = foldIcsLine(`SUMMARY:${'party '.repeat(30)}`);
        const lines = folded.split('\r\n');

        expect(lines.length).toBeGreaterThan(1);
        expect(lines[0]).toHaveLength(75);
        expect(lines.slice(1).every((line) => line.startsWith(' '))).toBe(true);
    });
});

describe('icsStatus', () => {
    it('translates Belsnickel\'s verdicts', () => {
        expect(icsStatus({ status: 'planned' })).toBe('TENTATIVE');
        expect(icsStatus({ status: 'confirmed' })).toBe('CONFIRMED');
        expect(icsStatus({ status: 'cancelled', cancelled: true })).toBe('CANCELLED');
    });
});

describe('toIcs', () => {
    it('wraps the events in one calendar', () => {
        const ics = toIcs([christmas], { stamp: '2026-11-01T09:00:00Z', origin: 'https://ppc.example' });

        expect(ics.startsWith('BEGIN:VCALENDAR\r\n')).toBe(true);
        expect(ics.endsWith('END:VCALENDAR\r\n')).toBe(true);
        expect(ics).toContain('DTSTAMP:20261101T090000Z');
        expect(ics).toContain('DTSTART:20261218T160000Z');
        expect(ics).toContain('DTEND:20261218T180000Z');
        expect(ics).toContain('SUMMARY:Christmas Party');
        expect(ics).toContain('LOCATION:Scranton\\, PA');
        expect(ics).toContain('STATUS:TENTATIVE');
        expect(ics).toContain('UID:party-1@dundermifflin.invalid');
        expect(ics).toContain('URL:https://ppc.example/parties/1');
    });

    it('omits the URL when no origin is known', () => {
        expect(toIcs([christmas], { stamp: '2026-11-01T09:00:00Z' })).not.toContain('URL:');
    });

    it('falls back to the start when the end is impish', () => {
        const ics = toIcs([{ ...christmas, end: 'nonsense' }], { stamp: '2026-11-01T09:00:00Z' });

        expect(ics).toContain('DTEND:20261218T160000Z');
    });

    it('leaves out a party with no start rather than corrupting the file', () => {
        const ics = toIcs([{ ...christmas, start: '' }], { stamp: '2026-11-01T09:00:00Z' });

        expect(ics).not.toContain('BEGIN:VEVENT');
    });

    it('survives an empty calendar', () => {
        expect(toIcs([], { stamp: '2026-11-01T09:00:00Z' }))
            .toBe('BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Dunder Mifflin Party Planning Committee//Belsnickel//EN\r\nCALSCALE:GREGORIAN\r\nMETHOD:PUBLISH\r\nX-WR-CALNAME:Dunder Mifflin Party Planning Committee\r\nEND:VCALENDAR\r\n');
    });
});

describe('escapeCsvCell', () => {
    it('quotes every cell and doubles inner quotes', () => {
        expect(escapeCsvCell('Dwight "The Beet" Schrute')).toBe('"Dwight ""The Beet"" Schrute"');
        expect(escapeCsvCell(undefined)).toBe('""');
        expect(escapeCsvCell(12)).toBe('"12"');
    });

    it('defuses a formula a spreadsheet would otherwise run', () => {
        expect(escapeCsvCell('=cmd|calc')).toBe('"\'=cmd|calc"');
        expect(escapeCsvCell('+1')).toBe('"\'+1"');
        expect(escapeCsvCell('-1')).toBe('"\'-1"');
        expect(escapeCsvCell('@SUM(A1)')).toBe('"\'@SUM(A1)"');
    });
});

describe('toCsv', () => {
    it('writes a header and one row per party', () => {
        const rows = toCsv([christmas]).trim().split('\r\n');

        expect(rows).toHaveLength(2);
        expect(rows[0]).toContain('"title"');
        expect(rows[1]).toContain('"Christmas Party"');
        expect(rows[1]).toContain('"Scranton, PA"');
        expect(rows[1]).toContain('"12"');
    });
});

describe('toJson', () => {
    it('hands back the events unchanged', () => {
        expect(JSON.parse(toJson([christmas]))).toEqual([christmas]);
    });
});

describe('buildExport and filenameFor', () => {
    it('names each file after its format', () => {
        expect(filenameFor('ics')).toBe('dunder-mifflin-parties.ics');
        expect(filenameFor('csv')).toBe('dunder-mifflin-parties.csv');
        expect(filenameFor('json')).toBe('dunder-mifflin-parties.json');
        expect(filenameFor('exe')).toBe('dunder-mifflin-parties.txt');
    });

    it('refuses a format Belsnickel never named', () => {
        expect(buildExport([christmas], 'exe')).toBeNull();
    });

    it('builds the requested format', () => {
        const file = buildExport([christmas], 'csv');

        expect(file.filename).toBe('dunder-mifflin-parties.csv');
        expect(file.mime).toBe('text/csv;charset=utf-8');
        expect(file.contents).toContain('Christmas Party');
    });
});

describe('exportSummary', () => {
    it('judges an empty calendar impish', () => {
        expect(exportSummary('ics', 0))
            .toBe('Nothing to take home. Belsnickel judges an empty calendar impish.');
    });

    it('counts what was written', () => {
        expect(exportSummary('ics', 1)).toContain('1 party written to dunder-mifflin-parties.ics');
        expect(exportSummary('csv', 3)).toContain('3 parties written to dunder-mifflin-parties.csv');
    });
});

describe('bindCalendarExport', () => {
    const markup = `
        <section data-calendar-export hidden>
            <button type="button" data-calendar-download="ics"></button>
            <button type="button" data-calendar-download="csv"></button>
            <button type="button" data-calendar-download="exe"></button>
            <p data-calendar-export-summary></p>
        </section>`;

    it('returns null when the page offers no downloads', () => {
        document.body.innerHTML = '<p>Nothing to take home.</p>';

        expect(bindCalendarExport(document, [christmas])).toBeNull();
    });

    it('reveals the buttons and offers the file when one is clicked', () => {
        document.body.innerHTML = markup;
        const created = vi.fn(() => 'blob:belsnickel');
        const revoked = vi.fn();
        window.URL.createObjectURL = created;
        window.URL.revokeObjectURL = revoked;

        bindCalendarExport(document, [christmas]);

        expect(document.querySelector('[data-calendar-export]').hidden).toBe(false);

        document.querySelector('[data-calendar-download="ics"]').click();

        expect(created).toHaveBeenCalledTimes(1);
        expect(revoked).toHaveBeenCalledWith('blob:belsnickel');
        expect(document.querySelector('[data-calendar-export-summary]').textContent)
            .toContain('dunder-mifflin-parties.ics');
        // Admirable housekeeping: the temporary link never stays behind.
        expect(document.querySelectorAll('a')).toHaveLength(0);
    });

    it('says nothing when an unknown format is demanded', () => {
        document.body.innerHTML = markup;
        window.URL.createObjectURL = vi.fn(() => 'blob:belsnickel');
        window.URL.revokeObjectURL = vi.fn();

        const run = bindCalendarExport(document, [christmas]);

        expect(run('exe')).toBeNull();
        expect(document.querySelector('[data-calendar-export-summary]').textContent).toBe('');
    });
});
