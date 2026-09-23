<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Tests\Repository;

use DateTimeImmutable;
use PartyPlanningCommittee\Model\Party;
use PartyPlanningCommittee\Model\Rsvp;
use PartyPlanningCommittee\Repository\Database;
use PartyPlanningCommittee\Repository\PartyRepository;
use PartyPlanningCommittee\Repository\RsvpRepository;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Belsnickel checks the guest list twice.
 */
final class RsvpRepositoryTest extends TestCase
{
    private PDO $pdo;
    private RsvpRepository $rsvps;
    private int $partyId;

    protected function setUp(): void
    {
        $this->pdo = Database::inMemory();
        $this->rsvps = new RsvpRepository($this->pdo);

        $party = (new PartyRepository($this->pdo))->save(new Party(
            null,
            'scranton',
            'Christmas Party',
            'Belsnickel judgment',
            new DateTimeImmutable('2026-12-18 16:00:00'),
            25000
        ));

        $this->partyId = (int) $party->id();
    }

    public function testItSavesAndListsRsvps(): void
    {
        $this->rsvps->save(new Rsvp(null, $this->partyId, 'Pam Beesly', Rsvp::STATUS_YES, 'Cookies'));
        $this->rsvps->save(new Rsvp(null, $this->partyId, 'Dwight Schrute', Rsvp::STATUS_YES, 'Beets'));

        $list = $this->rsvps->findByParty($this->partyId);

        $this->assertCount(2, $list);
        $this->assertSame('Dwight Schrute', $list[0]->employeeName());
        $this->assertSame('Pam Beesly', $list[1]->employeeName());
    }

    public function testASecondRsvpFromTheSameGuestOverwritesTheFirst(): void
    {
        $this->rsvps->save(new Rsvp(null, $this->partyId, 'Stanley Hudson', Rsvp::STATUS_MAYBE));
        $this->rsvps->save(new Rsvp(null, $this->partyId, 'Stanley Hudson', Rsvp::STATUS_NO, 'Nothing'));

        $list = $this->rsvps->findByParty($this->partyId);

        $this->assertCount(1, $list);
        $this->assertSame(Rsvp::STATUS_NO, $list[0]->status());
        $this->assertSame('Nothing', $list[0]->dish());
    }

    public function testItCountsOnlyThoseWhoSaidYes(): void
    {
        $this->rsvps->save(new Rsvp(null, $this->partyId, 'Pam Beesly', Rsvp::STATUS_YES));
        $this->rsvps->save(new Rsvp(null, $this->partyId, 'Jim Halpert', Rsvp::STATUS_YES));
        $this->rsvps->save(new Rsvp(null, $this->partyId, 'Stanley Hudson', Rsvp::STATUS_MAYBE));
        $this->rsvps->save(new Rsvp(null, $this->partyId, 'Creed Bratton', Rsvp::STATUS_NO));

        $this->assertSame(2, $this->rsvps->countAttending($this->partyId));
        $this->assertSame(0, $this->rsvps->countAttending(999));
    }

    public function testItFindsAGuest(): void
    {
        $this->rsvps->save(new Rsvp(null, $this->partyId, 'Kevin Malone', Rsvp::STATUS_YES, 'Chili'));

        $found = $this->rsvps->findByGuest($this->partyId, ' Kevin Malone ');

        $this->assertInstanceOf(Rsvp::class, $found);
        $this->assertSame('Chili', $found->dish());
        $this->assertNull($this->rsvps->findByGuest($this->partyId, 'Robert California'));
    }

    public function testItDeletesAnRsvp(): void
    {
        $saved = $this->rsvps->save(new Rsvp(null, $this->partyId, 'Toby Flenderson', Rsvp::STATUS_YES));

        $this->assertTrue($this->rsvps->delete((int) $saved->id()));
        $this->assertFalse($this->rsvps->delete((int) $saved->id()));
        $this->assertSame([], $this->rsvps->findByParty($this->partyId));
    }

    public function testRsvpsDieWithTheirParty(): void
    {
        $this->rsvps->save(new Rsvp(null, $this->partyId, 'Meredith Palmer', Rsvp::STATUS_YES));

        (new PartyRepository($this->pdo))->delete($this->partyId);

        $this->assertSame([], $this->rsvps->findByParty($this->partyId));
    }
}
