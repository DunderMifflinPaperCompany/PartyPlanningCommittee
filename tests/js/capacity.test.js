// Belsnickel counts chairs, and counts them the same way in every language.
import { describe, expect, it } from 'vitest';
import {
    bindCapacityMeter,
    capacityVerdict,
    isOverCapacity,
    readNumber,
} from '../../public/assets/js/capacity.js';

describe('isOverCapacity', () => {
    it('matches Office::canSeat(): equal to capacity is still admirable', () => {
        expect(isOverCapacity(9, 10)).toBe(false);
        expect(isOverCapacity(10, 10)).toBe(false);
        expect(isOverCapacity(11, 10)).toBe(true);
    });

    it('refuses to judge numbers that are not numbers', () => {
        expect(isOverCapacity(Number.NaN, 10)).toBe(false);
        expect(isOverCapacity(10, Number.NaN)).toBe(false);
        expect(isOverCapacity(Number.POSITIVE_INFINITY, 10)).toBe(false);
    });
});

describe('capacityVerdict', () => {
    it('declares an overcrowded room impish', () => {
        expect(capacityVerdict(12, 10))
            .toBe('Impish! More guests than chairs. Belsnickel is displeased.');
    });

    it('warns when the last chair is taken', () => {
        expect(capacityVerdict(10, 10))
            .toBe('Every chair is claimed. Admirable, but one more yes turns it impish.');
    });

    it('counts the remaining seats', () => {
        expect(capacityVerdict(4, 10)).toBe('6 seats remain. Belsnickel approves, for now.');
    });
});

describe('readNumber', () => {
    it('reads an integer attribute', () => {
        const element = document.createElement('p');
        element.setAttribute('data-attending', '7');

        expect(readNumber(element, 'data-attending')).toBe(7);
    });

    it('returns NaN for missing or blank attributes instead of pretending zero', () => {
        const element = document.createElement('p');
        element.setAttribute('data-capacity', '  ');

        expect(readNumber(element, 'data-capacity')).toBeNaN();
        expect(readNumber(element, 'data-attending')).toBeNaN();
    });
});

describe('bindCapacityMeter', () => {
    it('returns null when there is no meter to judge', () => {
        document.body.innerHTML = '<p>No branch here.</p>';

        expect(bindCapacityMeter(document)).toBeNull();
    });

    it('returns null when the numbers are impish', () => {
        document.body.innerHTML =
            '<p data-capacity-meter data-attending="lots" data-capacity="10" hidden></p>';

        expect(bindCapacityMeter(document)).toBeNull();
        expect(document.querySelector('[data-capacity-meter]').hidden).toBe(true);
    });

    it('reveals an admirable verdict without the warning class', () => {
        document.body.innerHTML =
            '<p data-capacity-meter data-attending="4" data-capacity="10" hidden></p>';

        const verdict = bindCapacityMeter(document);
        const meter = document.querySelector('[data-capacity-meter]');

        expect(verdict).toBe('6 seats remain. Belsnickel approves, for now.');
        expect(meter.textContent).toBe(verdict);
        expect(meter.hidden).toBe(false);
        expect(meter.classList.contains('warning')).toBe(false);
    });

    it('marks an overcrowded party with the warning class', () => {
        document.body.innerHTML =
            '<p data-capacity-meter data-attending="14" data-capacity="10" hidden></p>';

        bindCapacityMeter(document);
        const meter = document.querySelector('[data-capacity-meter]');

        expect(meter.classList.contains('warning')).toBe(true);
        expect(meter.textContent).toContain('Impish!');
    });
});
