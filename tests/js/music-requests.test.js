// Belsnickel checks the dance floor queue: ranked by votes, banned songs refused.
import { describe, expect, it } from 'vitest';
import {
    bindMusicRequests,
    isAllowedSong,
    queueSummary,
    rankRequests,
    readRequestRow,
    requestVerdict,
} from '../../public/assets/js/music-requests.js';

function queueMarkup() {
    return `
        <section data-music-requests data-max-requests="12" hidden>
            <form data-music-request-form>
                <input type="text" data-music-request-song>
            </form>
            <p data-music-request-verdict></p>
            <ul data-music-request-list>
                <li data-song="Y.M.C.A." data-requester="Michael" data-votes="4">
                    <button type="button" data-music-request-vote>Vote</button>
                </li>
                <li data-song="Sandstorm" data-requester="Kevin" data-votes="7">
                    <button type="button" data-music-request-vote>Vote</button>
                </li>
            </ul>
            <p data-music-request-summary></p>
        </section>
    `;
}

describe('isAllowedSong', () => {
    it('refuses banned songs and empty requests', () => {
        expect(isAllowedSong('Closing Time')).toBe(false);
        expect(isAllowedSong('   ')).toBe(false);
    });

    it('admits an honest request', () => {
        expect(isAllowedSong(' Sandstorm ')).toBe(true);
    });
});

describe('requestVerdict', () => {
    it('judges an empty request impish', () => {
        expect(requestVerdict('  ')).toBe('Impish! A request without a song is no request at all.');
    });

    it('judges a banned song impish', () => {
        expect(requestVerdict('Closing Time'))
            .toBe('Impish! “Closing Time” is banned from this dance floor.');
    });

    it('admits an allowed song', () => {
        expect(requestVerdict('Sandstorm')).toBe('“Sandstorm” joins the queue. Admirable, for now.');
    });
});

describe('readRequestRow', () => {
    it('reads the song, the requester and the votes', () => {
        document.body.innerHTML = '<li data-song=" Sandstorm " data-requester="Kevin" data-votes="7"></li>';

        expect(readRequestRow(document.querySelector('li')))
            .toEqual({ title: 'Sandstorm', requester: 'Kevin', votes: 7 });
    });

    it('treats an unreadable vote count as no votes at all', () => {
        document.body.innerHTML = '<li data-song="Sandstorm" data-votes="impish"></li>';

        expect(readRequestRow(document.querySelector('li')).votes).toBe(0);
    });
});

describe('rankRequests', () => {
    it('puts the loudest song first without touching the original list', () => {
        const requests = [
            { title: 'Y.M.C.A.', requester: 'Michael', votes: 4 },
            { title: 'Sandstorm', requester: 'Kevin', votes: 7 },
        ];

        expect(rankRequests(requests).map((request) => request.title))
            .toEqual(['Sandstorm', 'Y.M.C.A.']);
        expect(requests[0].title).toBe('Y.M.C.A.');
    });
});

describe('queueSummary', () => {
    it('calls a silent dance floor impish', () => {
        expect(queueSummary([])).toBe('The dance floor is silent. Belsnickel judges this queue impish.');
    });

    it('counts a single song', () => {
        expect(queueSummary([{ title: 'Sandstorm', votes: 7 }]))
            .toBe('1 song waits with 7 votes. Admirable restraint.');
    });

    it('counts the whole queue', () => {
        expect(queueSummary([{ title: 'Sandstorm', votes: 7 }, { title: 'Y.M.C.A.', votes: 4 }]))
            .toBe('2 songs wait with 11 votes. Belsnickel approves, for now.');
    });
});

describe('bindMusicRequests', () => {
    it('ignores a page without a dance floor', () => {
        document.body.innerHTML = '<p>Belsnickel is displeased</p>';

        expect(bindMusicRequests(document)).toBeNull();
    });

    it('unhides the queue and summarises it', () => {
        document.body.innerHTML = queueMarkup();

        expect(typeof bindMusicRequests(document)).toBe('function');
        expect(document.querySelector('[data-music-requests]').hidden).toBe(false);
        expect(document.querySelector('[data-music-request-summary]').textContent)
            .toBe('2 songs wait with 11 votes. Belsnickel approves, for now.');
    });

    it('adds an allowed song to the queue', () => {
        document.body.innerHTML = queueMarkup();
        bindMusicRequests(document);

        document.querySelector('[data-music-request-song]').value = 'Electric Slide';
        document.querySelector('[data-music-request-form]')
            .dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));

        expect(document.querySelectorAll('[data-song]')).toHaveLength(3);
        expect(document.querySelector('[data-music-request-verdict]').textContent)
            .toBe('“Electric Slide” joins the queue. Admirable, for now.');
    });

    it('refuses a banned song', () => {
        document.body.innerHTML = queueMarkup();
        bindMusicRequests(document);

        document.querySelector('[data-music-request-song]').value = 'Closing Time';
        document.querySelector('[data-music-request-form]')
            .dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));

        expect(document.querySelectorAll('[data-song]')).toHaveLength(2);
        expect(document.querySelector('[data-music-request-verdict]').textContent)
            .toBe('Impish! “Closing Time” is banned from this dance floor.');
    });

    it('counts a vote and re-ranks the queue', () => {
        document.body.innerHTML = queueMarkup();
        bindMusicRequests(document);

        document.querySelector('[data-song="Y.M.C.A."] [data-music-request-vote]').click();

        expect(document.querySelector('[data-song="Y.M.C.A."]').getAttribute('data-votes')).toBe('5');
        expect(document.querySelector('[data-music-request-summary]').textContent)
            .toBe('2 songs wait with 12 votes. Belsnickel approves, for now.');
    });
});
