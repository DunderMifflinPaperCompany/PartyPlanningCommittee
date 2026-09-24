<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Tests\Http;

use DateTimeImmutable;
use DateTimeZone;
use PartyPlanningCommittee\Http\CalendarFeed;
use PartyPlanningCommittee\Model\Party;
use PartyPlanningCommittee\Repository\OfficeRepository;
use PHPUnit\Framework\TestCase;

/**
 * Belsnickel inspects the calendar payload before the browser ever sees it. A feed nobody
 * checks is an impish feed waiting to publish the wrong afternoon.
 */
final class CalendarFeedTest extends TestCase
{
    private function party(?int $id = 7, string $status = Party::STATUS_PLANNED): Party
    {
        return new Party(
            $id,
            'scranton',
            'Christmas Party',
            'Belsnickel judgment',
            new DateTimeImmutable('2026-12-18 16:00:00', new DateTimeZone('UTC')),
            25000,
            $status,
            'Angela Martin'
        );
    }

    public function testItShapesAPartyIntoAnEvent(): void
    {
        $events = CalendarFeed::events([$this->party()], new OfficeRepository(), [7 => 12]);

        $this->assertCount(1, $events);
        $this->assertSame(7, $events[0]['id']);
        $this->assertSame('Christmas Party', $events[0]['title']);
        $this->assertSame('Scranton, PA', $events[0]['location']);
        $this->assertSame('Angela Martin', $events[0]['organizer']);
        $this->assertSame('/parties/7', $events[0]['url']);
        $this->assertSame('2026-12-18', $events[0]['date']);
        $this->assertSame('2026-12-18T16:00:00Z', $events[0]['start']);
        $this->assertSame('2026-12-18T18:00:00Z', $events[0]['end']);
        $this->assertSame(12, $events[0]['attending']);
        $this->assertSame('$250.00', $events[0]['budget']);
        $this->assertFalse($events[0]['cancelled']);
    }

    public function testItMarksACancelledParty(): void
    {
        $events = CalendarFeed::events([$this->party(7, Party::STATUS_CANCELLED)], new OfficeRepository());

        $this->assertTrue($events[0]['cancelled']);
        $this->assertSame(Party::STATUS_CANCELLED, $events[0]['status']);
        $this->assertSame(0, $events[0]['attending']);
    }

    public function testItRefusesAPartyWithNoIdentifier(): void
    {
        // Impish record! An unsaved party has no page, so Belsnickel leaves it off the wall.
        $this->assertSame([], CalendarFeed::events([$this->party(null)], new OfficeRepository()));
    }

    public function testTheJsonIslandCannotCloseTheScriptTag(): void
    {
        $party = new Party(
            3,
            'utica',
            '</script><script>alert(1)</script>',
            'Impish mischief',
            new DateTimeImmutable('2026-11-05 12:00:00', new DateTimeZone('UTC')),
            1000
        );

        $json = CalendarFeed::toJson(CalendarFeed::events([$party], new OfficeRepository()));

        $this->assertStringNotContainsString('</script>', $json);
        $this->assertStringNotContainsString('<script>', $json);
        $this->assertIsArray(json_decode($json, true));
    }
}
