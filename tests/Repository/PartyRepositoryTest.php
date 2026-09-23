<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Tests\Repository;

use DateTimeImmutable;
use PartyPlanningCommittee\Model\Party;
use PartyPlanningCommittee\Repository\Database;
use PartyPlanningCommittee\Repository\PartyRepository;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Belsnickel audits the ledger against a throwaway in-memory database.
 * Admirable: no test ever touches the real committee records.
 */
final class PartyRepositoryTest extends TestCase
{
    private PDO $pdo;
    private PartyRepository $repository;

    protected function setUp(): void
    {
        $this->pdo = Database::inMemory();
        $this->repository = new PartyRepository($this->pdo);
    }

    private function party(string $office = 'scranton', string $when = '2026-12-18 16:00:00', string $status = Party::STATUS_PLANNED): Party
    {
        return new Party(
            null,
            $office,
            'Christmas Party',
            'Belsnickel judgment',
            new DateTimeImmutable($when),
            25000,
            $status,
            'Angela Martin'
        );
    }

    public function testItSavesAndFindsAParty(): void
    {
        $saved = $this->repository->save($this->party());

        $this->assertNotNull($saved->id());

        $found = $this->repository->find((int) $saved->id());

        $this->assertInstanceOf(Party::class, $found);
        $this->assertSame('Christmas Party', $found->title());
        $this->assertSame(25000, $found->budgetCents());
    }

    public function testItReturnsNullForAMissingParty(): void
    {
        $this->assertNull($this->repository->find(404));
    }

    public function testItUpdatesAnExistingPartyInsteadOfDuplicating(): void
    {
        $saved = $this->repository->save($this->party());
        $this->repository->save($saved->withStatus(Party::STATUS_CONFIRMED));

        $this->assertCount(1, $this->repository->all());
        $this->assertSame(Party::STATUS_CONFIRMED, $this->repository->find((int) $saved->id())->status());
    }

    public function testItListsPartiesByDate(): void
    {
        $this->repository->save($this->party('scranton', '2026-12-18 16:00:00'));
        $this->repository->save($this->party('utica', '2026-11-05 12:00:00'));

        $all = $this->repository->all();

        $this->assertCount(2, $all);
        $this->assertSame('utica', $all[0]->officeSlug());
        $this->assertSame('scranton', $all[1]->officeSlug());
    }

    public function testItFiltersByOffice(): void
    {
        $this->repository->save($this->party('scranton'));
        $this->repository->save($this->party('utica'));
        $this->repository->save($this->party('nashua'));

        $this->assertCount(1, $this->repository->findByOffice('utica'));
        $this->assertCount(1, $this->repository->findByOffice('  UTICA '));
        $this->assertCount(0, $this->repository->findByOffice('stamford'));
    }

    public function testItListsOnlyUpcomingAndUncancelledParties(): void
    {
        $this->repository->save($this->party('scranton', '2026-01-05 16:00:00'));
        $this->repository->save($this->party('utica', '2026-12-18 16:00:00'));
        $this->repository->save($this->party('nashua', '2026-12-20 16:00:00', Party::STATUS_CANCELLED));

        $upcoming = $this->repository->upcoming(new DateTimeImmutable('2026-06-01 00:00:00'));

        $this->assertCount(1, $upcoming);
        $this->assertSame('utica', $upcoming[0]->officeSlug());
    }

    public function testItCancelsAParty(): void
    {
        $saved = $this->repository->save($this->party());
        $cancelled = $this->repository->cancel((int) $saved->id());

        $this->assertInstanceOf(Party::class, $cancelled);
        $this->assertTrue($cancelled->isCancelled());
        $this->assertTrue($this->repository->find((int) $saved->id())->isCancelled());
    }

    public function testCancellingAGhostPartyChangesNothing(): void
    {
        $this->assertNull($this->repository->cancel(999));
    }

    public function testItDeletesAParty(): void
    {
        $saved = $this->repository->save($this->party());

        $this->assertTrue($this->repository->delete((int) $saved->id()));
        $this->assertFalse($this->repository->delete((int) $saved->id()));
        $this->assertNull($this->repository->find((int) $saved->id()));
    }

    public function testItCountsPartiesByOffice(): void
    {
        $this->repository->save($this->party('scranton'));
        $this->repository->save($this->party('scranton', '2026-12-19 16:00:00'));
        $this->repository->save($this->party('nashua'));

        $counts = $this->repository->countByOffice();

        $this->assertSame(2, $counts['scranton']);
        $this->assertSame(1, $counts['nashua']);
        $this->assertArrayNotHasKey('utica', $counts);
    }

    public function testItPublishesAndUnpublishesAParty(): void
    {
        $saved = $this->repository->save($this->party());

        $this->assertFalse($saved->isPublished());
        $this->assertTrue($this->repository->setPublished((int) $saved->id(), true)->isPublished());
        $this->assertTrue($this->repository->find((int) $saved->id())->isPublished());
        $this->assertFalse($this->repository->setPublished((int) $saved->id(), false)->isPublished());
        $this->assertFalse($this->repository->find((int) $saved->id())->isPublished());
    }

    public function testItAddsThePublishedColumnToAnOlderLedger(): void
    {
        $legacy = Database::connect('sqlite::memory:');
        $legacy->exec(
            'CREATE TABLE parties (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                office_slug TEXT NOT NULL,
                title TEXT NOT NULL,
                theme TEXT NOT NULL,
                scheduled_for TEXT NOT NULL,
                budget_cents INTEGER NOT NULL,
                status TEXT NOT NULL,
                organizer TEXT NOT NULL
            )'
        );

        // Admirable: migrating twice is harmless. Dropping the old table would be impish.
        Database::migrate($legacy);
        Database::migrate($legacy);

        $repository = new PartyRepository($legacy);
        $saved = $repository->save($this->party());

        $this->assertFalse($repository->find((int) $saved->id())->isPublished());
    }

    public function testPublishingAGhostPartyChangesNothing(): void
    {
        $this->assertNull($this->repository->setPublished(999, true));
    }

    public function testItSurvivesImpishSqlInTitles(): void
    {
        $party = new Party(
            null,
            'scranton',
            "Dwight's '; DROP TABLE parties; -- party",
            'Sabotage',
            new DateTimeImmutable('2026-12-18 16:00:00'),
            100,
            Party::STATUS_PLANNED,
            'Dwight Schrute'
        );

        $saved = $this->repository->save($party);

        $this->assertCount(1, $this->repository->all());
        $this->assertSame("Dwight's '; DROP TABLE parties; -- party", $this->repository->find((int) $saved->id())->title());
    }
}
