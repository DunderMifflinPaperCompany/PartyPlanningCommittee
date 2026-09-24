// Belsnickel checks the calendar grid, because a wall calendar that misplaces a Saturday
// is impish and sends the whole branch to the wrong party.
import { describe, expect, it } from 'vitest';
import {
    bindCalendar,
    buildMonthGrid,
    groupByDate,
    monthHeading,
    monthKey,
    monthSummary,
    readCalendarEvents,
    renderMonthGrid,
} from '../../public/assets/js/calendar.js';

const christmas = {
    id: 1,
    title: 'Christmas Party',
    date: '2026-12-18',
    time: '4:00pm',
    start: '2026-12-18T16:00:00Z',
    end: '2026-12-18T18:00:00Z',
    status: 'planned',
    cancelled: false,
    url: '/parties/1',
};

const cancelled = {
    id: 2,
    title: 'Ice Cream Social',
    date: '2026-12-24',
    time: '1:00pm',
    status: 'cancelled',
    cancelled: true,
    url: '/parties/2',
};

describe('readCalendarEvents', () => {
    it('reads the data island the server left behind', () => {
        document.body.innerHTML =
            `<script type="application/json" data-calendar-events>${JSON.stringify([christmas])}</script>`;

        expect(readCalendarEvents(document)).toEqual([christmas]);
    });

    it('returns an empty list rather than throwing on a missing or impish island', () => {
        document.body.innerHTML = '<p>No calendar here.</p>';
        expect(readCalendarEvents(document)).toEqual([]);

        document.body.innerHTML =
            '<script type="application/json" data-calendar-events>{ not json </script>';
        expect(readCalendarEvents(document)).toEqual([]);

        document.body.innerHTML =
            '<script type="application/json" data-calendar-events>{"impish":true}</script>';
        expect(readCalendarEvents(document)).toEqual([]);
    });
});

describe('monthKey and monthHeading', () => {
    it('pads the month and rolls overflowing months into the next year', () => {
        expect(monthKey(2026, 0)).toBe('2026-01');
        expect(monthKey(2026, 11)).toBe('2026-12');
        expect(monthKey(2026, 12)).toBe('2027-01');
        expect(monthHeading(2026, 11)).toBe('December 2026');
        expect(monthHeading(2026, -1)).toBe('December 2025');
    });
});

describe('groupByDate', () => {
    it('gathers parties that share a day and drops the dateless', () => {
        const grouped = groupByDate([christmas, { ...cancelled, date: '2026-12-18' }, { title: 'Impish' }]);

        expect(grouped.size).toBe(1);
        expect(grouped.get('2026-12-18')).toHaveLength(2);
    });
});

describe('buildMonthGrid', () => {
    it('pads the month into whole weeks starting on Sunday', () => {
        const weeks = buildMonthGrid(2026, 11, [christmas]);

        expect(weeks).toHaveLength(5);
        expect(weeks.every((week) => week.length === 7)).toBe(true);
        // 1 December 2026 is a Tuesday, so Belsnickel leaves two blanks before it.
        expect(weeks[0][0].date).toBeNull();
        expect(weeks[0][1].date).toBeNull();
        expect(weeks[0][2].date).toBe('2026-12-01');
        expect(weeks[4][6].date).toBeNull();
    });

    it('places each party on its own day', () => {
        const weeks = buildMonthGrid(2026, 11, [christmas, cancelled]);
        const cells = weeks.flat().filter((cell) => cell.events.length > 0);

        expect(cells.map((cell) => cell.date)).toEqual(['2026-12-18', '2026-12-24']);
    });

    it('handles a leap February without inventing a day', () => {
        const days = buildMonthGrid(2028, 1).flat().filter((cell) => cell.date !== null);

        expect(days).toHaveLength(29);
    });
});

describe('monthSummary', () => {
    it('judges an empty month idle', () => {
        expect(monthSummary([christmas], '2026-11'))
            .toBe('No parties this month. Belsnickel judges the committee idle.');
    });

    it('praises a month with no cancellations', () => {
        expect(monthSummary([christmas], '2026-12'))
            .toBe('1 party this month. Admirable planning.');
    });

    it('names the cancellations as impish', () => {
        expect(monthSummary([christmas, cancelled], '2026-12'))
            .toBe('2 parties this month. 1 cancelled, which Belsnickel judges impish.');
    });
});

describe('renderMonthGrid', () => {
    it('writes titles as text, never as markup', () => {
        document.body.innerHTML = '<table><tbody></tbody></table>';
        const tbody = document.querySelector('tbody');
        const impish = { ...christmas, title: '<img src=x onerror=alert(1)>' };

        const drawn = renderMonthGrid(tbody, buildMonthGrid(2026, 11, [impish]));

        expect(drawn).toBe(1);
        expect(tbody.querySelector('img')).toBeNull();
        expect(tbody.querySelector('.calendar-event').textContent)
            .toBe('4:00pm <img src=x onerror=alert(1)>');
    });

    it('replaces the previous month instead of stacking rows', () => {
        document.body.innerHTML = '<table><tbody></tbody></table>';
        const tbody = document.querySelector('tbody');

        renderMonthGrid(tbody, buildMonthGrid(2026, 11, [christmas]));
        renderMonthGrid(tbody, buildMonthGrid(2026, 10, [christmas]));

        expect(tbody.querySelectorAll('.calendar-event')).toHaveLength(0);
    });
});

describe('bindCalendar', () => {
    const markup = (events) => `
        <script type="application/json" data-calendar-events>${JSON.stringify(events)}</script>
        <section data-calendar hidden>
            <button type="button" data-calendar-prev></button>
            <h3 data-calendar-heading></h3>
            <button type="button" data-calendar-next></button>
            <table data-calendar-grid><tbody></tbody></table>
            <p data-calendar-summary></p>
        </section>`;

    it('returns null when the page carries no calendar', () => {
        document.body.innerHTML = '<p>No calendar here.</p>';

        expect(bindCalendar(document)).toBeNull();
    });

    it('reveals the month of the given day', () => {
        document.body.innerHTML = markup([christmas, cancelled]);

        const calendar = bindCalendar(document, new Date(2026, 11, 1));

        expect(document.querySelector('[data-calendar]').hidden).toBe(false);
        expect(document.querySelector('[data-calendar-heading]').textContent).toBe('December 2026');
        expect(document.querySelectorAll('.calendar-event')).toHaveLength(2);
        expect(document.querySelector('[data-calendar-summary]').textContent)
            .toContain('2 parties this month');
        expect(calendar.events).toHaveLength(2);
    });

    it('walks backwards and forwards across the year boundary', () => {
        document.body.innerHTML = markup([christmas]);

        bindCalendar(document, new Date(2026, 11, 1));
        document.querySelector('[data-calendar-next]').click();

        expect(document.querySelector('[data-calendar-heading]').textContent).toBe('January 2027');
        expect(document.querySelectorAll('.calendar-event')).toHaveLength(0);

        document.querySelector('[data-calendar-prev]').click();

        expect(document.querySelector('[data-calendar-heading]').textContent).toBe('December 2026');
        expect(document.querySelectorAll('.calendar-event')).toHaveLength(1);
    });

    it('refuses a grid with no body to draw into', () => {
        document.body.innerHTML = '<section data-calendar><table data-calendar-grid></table></section>';

        expect(bindCalendar(document)).toBeNull();
    });
});
