<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Tests\Model;

use DateTimeImmutable;
use InvalidArgumentException;
use PartyPlanningCommittee\Model\Party;
use PHPUnit\Framework\TestCase;

/**
 * Belsnickel tests the Party, because a party nobody checks is the impish kind.
 */
final class PartyTest extends TestCase
{
    private function party(array $overrides = []): Party
    {
        $defaults = [
            'id' => null,
            'office' => 'scranton',
            'title' => 'Christmas Party',
            'theme' => 'Belsnickel judgment',
            'when' => new DateTimeImmutable('2026-12-18 16:00:00'),
            'budget' => 25000,
            'status' => Party::STATUS_PLANNED,
            'organizer' => 'Angela Martin',
        ];

        $values = array_merge($defaults, $overrides);

        return new Party(
            $values['id'],
            $values['office'],
            $values['title'],
            $values['theme'],
            $values['when'],
            $values['budget'],
            $values['status'],
            $values['organizer']
        );
    }

    public function testItExposesItsDetails(): void
    {
        $party = $this->party(['id' => 7]);

        $this->assertSame(7, $party->id());
        $this->assertSame('scranton', $party->officeSlug());
        $this->assertSame('Christmas Party', $party->title());
        $this->assertSame('Belsnickel judgment', $party->theme());
        $this->assertSame(25000, $party->budgetCents());
        $this->assertSame(Party::STATUS_PLANNED, $party->status());
        $this->assertSame('Angela Martin', $party->organizer());
    }

    public function testItFormatsTheBudgetInDollars(): void
    {
        $this->assertSame('$250.00', $this->party()->formattedBudget());
        $this->assertSame('$0.00', $this->party(['budget' => 0])->formattedBudget());
        $this->assertSame('$1,234.56', $this->party(['budget' => 123456])->formattedBudget());
    }

    public function testItRejectsAnEmptyTitle(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->party(['title' => '   ']);
    }

    public function testItRejectsAnOverlongTitle(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->party(['title' => str_repeat('a', 121)]);
    }

    public function testItRejectsAnEmptyTheme(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->party(['theme' => '']);
    }

    public function testItRejectsAnEmptyOffice(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->party(['office' => ' ']);
    }

    public function testItRejectsANegativeBudget(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->party(['budget' => -1]);
    }

    public function testItRejectsAnUnknownStatus(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->party(['status' => 'impish']);
    }

    public function testItRejectsANonPositiveIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->party(['id' => 0]);
    }

    public function testItFallsBackToTheCommitteeAsOrganizer(): void
    {
        $this->assertSame('The Party Planning Committee', $this->party(['organizer' => '  '])->organizer());
    }

    public function testItKnowsWhenItIsStillAhead(): void
    {
        $party = $this->party(['when' => new DateTimeImmutable('2026-12-18 16:00:00')]);

        $this->assertTrue($party->isUpcoming(new DateTimeImmutable('2026-12-01 09:00:00')));
        $this->assertFalse($party->isUpcoming(new DateTimeImmutable('2026-12-19 09:00:00')));
    }

    public function testACancelledPartyIsNeverUpcoming(): void
    {
        $party = $this->party(['status' => Party::STATUS_CANCELLED]);

        $this->assertFalse($party->isUpcoming(new DateTimeImmutable('2026-12-01 09:00:00')));
        $this->assertTrue($party->isCancelled());
    }

    public function testItClonesWithAnIdentifier(): void
    {
        $party = $this->party();
        $saved = $party->withId(12);

        $this->assertNull($party->id());
        $this->assertSame(12, $saved->id());
        $this->assertSame($party->title(), $saved->title());
    }

    public function testItClonesWithANewStatus(): void
    {
        $party = $this->party(['id' => 3]);
        $cancelled = $party->withStatus(Party::STATUS_CANCELLED);

        $this->assertSame(Party::STATUS_PLANNED, $party->status());
        $this->assertSame(Party::STATUS_CANCELLED, $cancelled->status());
        $this->assertSame(3, $cancelled->id());
    }

    public function testItDividesTheBudgetPerGuest(): void
    {
        $this->assertSame(2500, $this->party()->budgetPerGuestCents(10));
        $this->assertSame(8333, $this->party()->budgetPerGuestCents(3));
    }

    public function testItRefusesToDivideAmongNoGuests(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->party()->budgetPerGuestCents(0);
    }

    public function testItRoundTripsThroughARow(): void
    {
        $party = $this->party(['id' => 5]);
        $restored = Party::fromRow($party->toRow());

        $this->assertSame($party->toRow(), $restored->toRow());
        $this->assertSame('2026-12-18 16:00:00', $party->toRow()['scheduled_for']);
    }
}
