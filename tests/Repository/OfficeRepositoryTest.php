<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Tests\Repository;

use PartyPlanningCommittee\Model\Office;
use PartyPlanningCommittee\Repository\OfficeRepository;
use PHPUnit\Framework\TestCase;

/**
 * Belsnickel confirms the branch roster has not been tampered with.
 */
final class OfficeRepositoryTest extends TestCase
{
    public function testItKnowsTheThreeBranches(): void
    {
        $repository = new OfficeRepository();

        $this->assertSame(['scranton', 'utica', 'nashua'], $repository->slugs());
        $this->assertCount(3, $repository->all());
    }

    public function testItFindsABranchCaseInsensitively(): void
    {
        $repository = new OfficeRepository();
        $office = $repository->find('  SCRANTON ');

        $this->assertInstanceOf(Office::class, $office);
        $this->assertSame('Scranton, PA', $office->label());
    }

    public function testItReturnsNullForAnImpishBranch(): void
    {
        $repository = new OfficeRepository();

        $this->assertNull($repository->find('stamford'));
        $this->assertFalse($repository->exists('stamford'));
        $this->assertTrue($repository->exists('nashua'));
    }

    public function testEveryBranchHasACapacity(): void
    {
        foreach ((new OfficeRepository())->all() as $office) {
            $this->assertGreaterThan(0, $office->capacity());
        }
    }
}
