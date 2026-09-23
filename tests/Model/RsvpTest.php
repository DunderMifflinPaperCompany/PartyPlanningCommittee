<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Tests\Model;

use InvalidArgumentException;
use PartyPlanningCommittee\Model\Rsvp;
use PHPUnit\Framework\TestCase;

/**
 * Belsnickel tests the RSVP. No RSVP, no entry, and no untested guest list.
 */
final class RsvpTest extends TestCase
{
    public function testItExposesItsDetails(): void
    {
        $rsvp = new Rsvp(4, 2, '  Pam Beesly ', Rsvp::STATUS_YES, ' Cookies ');

        $this->assertSame(4, $rsvp->id());
        $this->assertSame(2, $rsvp->partyId());
        $this->assertSame('Pam Beesly', $rsvp->employeeName());
        $this->assertSame(Rsvp::STATUS_YES, $rsvp->status());
        $this->assertSame('Cookies', $rsvp->dish());
    }

    public function testOnlyAFirmYesCountsAsAttending(): void
    {
        $this->assertTrue((new Rsvp(null, 1, 'Jim Halpert', Rsvp::STATUS_YES))->isAttending());
        $this->assertFalse((new Rsvp(null, 1, 'Stanley Hudson', Rsvp::STATUS_MAYBE))->isAttending());
        $this->assertFalse((new Rsvp(null, 1, 'Creed Bratton', Rsvp::STATUS_NO))->isAttending());
    }

    public function testItRejectsANamelessGuest(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Rsvp(null, 1, '   ', Rsvp::STATUS_YES);
    }

    public function testItRejectsAnOverlongName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Rsvp(null, 1, str_repeat('b', 81), Rsvp::STATUS_YES);
    }

    public function testItRejectsAnOverlongDish(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Rsvp(null, 1, 'Kevin Malone', Rsvp::STATUS_YES, str_repeat('chili ', 20));
    }

    public function testItRejectsAnUnknownStatus(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Rsvp(null, 1, 'Michael Scott', 'probably');
    }

    public function testItRejectsAnImpishPartyIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Rsvp(null, 0, 'Michael Scott', Rsvp::STATUS_YES);
    }

    public function testItRejectsANonPositiveIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Rsvp(-3, 1, 'Michael Scott', Rsvp::STATUS_YES);
    }

    public function testItClonesWithAnIdentifier(): void
    {
        $rsvp = new Rsvp(null, 1, 'Oscar Martinez', Rsvp::STATUS_NO);
        $saved = $rsvp->withId(9);

        $this->assertNull($rsvp->id());
        $this->assertSame(9, $saved->id());
        $this->assertSame('Oscar Martinez', $saved->employeeName());
    }

    public function testItRoundTripsThroughARow(): void
    {
        $rsvp = new Rsvp(2, 8, 'Phyllis Vance', Rsvp::STATUS_MAYBE, 'Ambrosia salad');

        $this->assertSame($rsvp->toRow(), Rsvp::fromRow($rsvp->toRow())->toRow());
    }

    public function testItToleratesAMissingDishColumn(): void
    {
        $rsvp = Rsvp::fromRow([
            'id' => 1,
            'party_id' => 3,
            'employee_name' => 'Toby Flenderson',
            'status' => Rsvp::STATUS_NO,
        ]);

        $this->assertSame('', $rsvp->dish());
    }
}
