<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Tests\Model;

use InvalidArgumentException;
use PartyPlanningCommittee\Model\Office;
use PHPUnit\Framework\TestCase;

/**
 * Belsnickel tests the Office. Untested value objects are impish.
 */
final class OfficeTest extends TestCase
{
    public function testItExposesItsBranchDetails(): void
    {
        $office = new Office('scranton', 'Scranton', 'PA', 40);

        $this->assertSame('scranton', $office->slug());
        $this->assertSame('Scranton', $office->name());
        $this->assertSame('PA', $office->state());
        $this->assertSame(40, $office->capacity());
        $this->assertSame('Scranton, PA', $office->label());
    }

    public function testItNormalisesTheSlug(): void
    {
        $office = new Office('  NASHUA  ', 'Nashua', 'NH', 30);

        $this->assertSame('nashua', $office->slug());
    }

    public function testItRejectsAnImpishSlug(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Office('scranton branch!', 'Scranton', 'PA', 40);
    }

    public function testItRejectsAnEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Office('utica', '   ', 'NY', 25);
    }

    public function testItRejectsAnEmptyState(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Office('utica', 'Utica', '', 25);
    }

    public function testItRejectsAnImpishCapacity(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Office('utica', 'Utica', 'NY', 0);
    }

    public function testItJudgesSeating(): void
    {
        $office = new Office('utica', 'Utica', 'NY', 25);

        $this->assertTrue($office->canSeat(0));
        $this->assertTrue($office->canSeat(25));
        $this->assertFalse($office->canSeat(26));
        $this->assertFalse($office->canSeat(-1));
    }

    public function testItConvertsToArray(): void
    {
        $office = new Office('nashua', 'Nashua', 'NH', 30);

        $this->assertSame(
            ['slug' => 'nashua', 'name' => 'Nashua', 'state' => 'NH', 'capacity' => 30],
            $office->toArray()
        );
    }
}
