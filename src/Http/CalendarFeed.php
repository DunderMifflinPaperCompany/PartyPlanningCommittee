<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Http;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use PartyPlanningCommittee\Model\Party;
use PartyPlanningCommittee\Repository\OfficeRepository;

/**
 * Belsnickel prepares the calendar payload. The browser draws the grid and writes the
 * ICS file, but it is fed only values Belsnickel has already judged and shaped here.
 *
 * Admirable: one place decides what an event looks like, so the month grid, the ICS
 * file, the CSV and the JSON can never quietly disagree with one another.
 */
final class CalendarFeed
{
    /**
     * Belsnickel grants every party two hours. A party without an end is impish: a
     * calendar entry that never closes would haunt the guest's week.
     */
    public const DEFAULT_DURATION_MINUTES = 120;

    /**
     * @param list<Party>    $parties
     * @param array<int,int> $attendance
     *
     * @return list<array<string, mixed>>
     */
    public static function events(array $parties, OfficeRepository $offices, array $attendance = []): array
    {
        $events = [];

        foreach ($parties as $party) {
            $id = $party->id();

            if ($id === null) {
                // Impish record! An unsaved party has no page to link to, so it is refused.
                continue;
            }

            $office = $offices->find($party->officeSlug());
            $start = $party->scheduledFor();
            $end = $start->add(new DateInterval('PT' . self::DEFAULT_DURATION_MINUTES . 'M'));

            $events[] = [
                'id' => $id,
                'title' => $party->title(),
                'theme' => $party->theme(),
                'officeSlug' => $party->officeSlug(),
                'location' => $office !== null ? $office->label() : ucfirst($party->officeSlug()),
                'organizer' => $party->organizer(),
                'status' => $party->status(),
                'cancelled' => $party->isCancelled(),
                'published' => $party->isPublished(),
                'attending' => (int) ($attendance[$id] ?? 0),
                'budget' => $party->formattedBudget(),
                'url' => '/parties/' . $id,
                'date' => $start->format('Y-m-d'),
                'time' => $start->format('g:ia'),
                'start' => self::utc($start),
                'end' => self::utc($end),
            ];
        }

        return $events;
    }

    /**
     * Belsnickel writes the data island himself. JSON smuggled into a script tag without
     * hex-escaping its angle brackets is impish, and earns far more than coal.
     *
     * @param list<array<string, mixed>> $events
     */
    public static function toJson(array $events): string
    {
        return (string) json_encode(
            $events,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * Belsnickel speaks in UTC to the calendar programs, so no branch may argue about
     * which afternoon the party belongs to.
     */
    private static function utc(DateTimeImmutable $moment): string
    {
        return $moment->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }
}
