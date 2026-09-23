<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Tests\Repository;

use DateTimeImmutable;
use PartyPlanningCommittee\Repository\Database;
use PartyPlanningCommittee\Repository\PartyRepository;
use PartyPlanningCommittee\Repository\RsvpRepository;
use PartyPlanningCommittee\Repository\Seeder;
use PHPUnit\Framework\TestCase;

/**
 * Belsnickel verifies the seed: three branches, and not a crumb more on a second run.
 */
final class SeederTest extends TestCase
{
    public function testItSeedsEveryBranchOnce(): void
    {
        $pdo = Database::inMemory();
        $parties = new PartyRepository($pdo);
        $rsvps = new RsvpRepository($pdo);

        $this->assertTrue(Seeder::seed($parties, $rsvps, new DateTimeImmutable('2026-01-01 09:00:00')));

        $counts = $parties->countByOffice();

        $this->assertSame(1, $counts['scranton']);
        $this->assertSame(1, $counts['utica']);
        $this->assertSame(1, $counts['nashua']);
    }

    public function testItRefusesToSeedTwice(): void
    {
        $pdo = Database::inMemory();
        $parties = new PartyRepository($pdo);
        $rsvps = new RsvpRepository($pdo);

        Seeder::seed($parties, $rsvps, new DateTimeImmutable('2026-01-01 09:00:00'));

        $this->assertFalse(Seeder::seed($parties, $rsvps, new DateTimeImmutable('2026-01-01 09:00:00')));
        $this->assertCount(3, $parties->all());
    }

    public function testTheSeededScrantonPartyHasGuests(): void
    {
        $pdo = Database::inMemory();
        $parties = new PartyRepository($pdo);
        $rsvps = new RsvpRepository($pdo);

        Seeder::seed($parties, $rsvps, new DateTimeImmutable('2026-01-01 09:00:00'));

        $scranton = $parties->findByOffice('scranton')[0];

        $this->assertSame(2, $rsvps->countAttending((int) $scranton->id()));
        $this->assertCount(3, $rsvps->findByParty((int) $scranton->id()));
    }

    public function testSeededPartiesAreStillAhead(): void
    {
        $pdo = Database::inMemory();
        $parties = new PartyRepository($pdo);
        $now = new DateTimeImmutable('2026-01-01 09:00:00');

        Seeder::seed($parties, new RsvpRepository($pdo), $now);

        $this->assertCount(3, $parties->upcoming($now));
    }
}
