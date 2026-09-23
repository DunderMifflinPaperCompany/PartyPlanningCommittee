<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Tests\Http;

use PartyPlanningCommittee\Http\PartyForm;
use PartyPlanningCommittee\Model\Party;
use PartyPlanningCommittee\Repository\OfficeRepository;
use PHPUnit\Framework\TestCase;

/**
 * Belsnickel tests the paperwork inspector. Sloppy validation is impish.
 */
final class PartyFormTest extends TestCase
{
    private PartyForm $form;

    protected function setUp(): void
    {
        $this->form = new PartyForm(new OfficeRepository());
    }

    /**
     * @return array<string, string>
     */
    private function validInput(): array
    {
        return [
            'office_slug' => 'scranton',
            'title' => 'Christmas Party',
            'theme' => 'Belsnickel judgment',
            'scheduled_for' => '2026-12-18T16:00',
            'budget' => '250.00',
            'status' => Party::STATUS_PLANNED,
            'organizer' => 'Angela Martin',
        ];
    }

    public function testItApprovesHonestInput(): void
    {
        $this->assertSame([], $this->form->validate($this->validInput()));
        $this->assertTrue($this->form->isApproved($this->validInput()));
    }

    public function testItRejectsAnUnknownBranch(): void
    {
        $errors = $this->form->validate(['office_slug' => 'stamford'] + $this->validInput());

        $this->assertNotSame([], $errors);
        $this->assertStringContainsString('Impish branch', $errors[0]);
    }

    public function testItRejectsAMissingTitleAndTheme(): void
    {
        $input = array_merge($this->validInput(), ['title' => '  ', 'theme' => '']);

        $this->assertCount(2, $this->form->validate($input));
    }

    public function testItRejectsAnImpishDate(): void
    {
        $input = array_merge($this->validInput(), ['scheduled_for' => 'sometime in December']);

        $this->assertNotSame([], $this->form->validate($input));
    }

    public function testItRejectsANegativeBudget(): void
    {
        $input = array_merge($this->validInput(), ['budget' => '-5']);

        $this->assertNotSame([], $this->form->validate($input));
    }

    public function testItRejectsANonNumericBudget(): void
    {
        $input = array_merge($this->validInput(), ['budget' => 'a sack of coal']);

        $this->assertNotSame([], $this->form->validate($input));
    }

    public function testItRejectsAnUnknownStatus(): void
    {
        $input = array_merge($this->validInput(), ['status' => 'impish']);

        $this->assertNotSame([], $this->form->validate($input));
    }

    public function testItRejectsEntirelyEmptyInput(): void
    {
        $this->assertCount(4, $this->form->validate([]));
    }

    public function testItBuildsAPartyWithCents(): void
    {
        $party = $this->form->toParty($this->validInput());

        $this->assertSame('scranton', $party->officeSlug());
        $this->assertSame(25000, $party->budgetCents());
        $this->assertSame('2026-12-18 16:00:00', $party->scheduledFor()->format('Y-m-d H:i:s'));
        $this->assertNull($party->id());
    }

    public function testItRoundsFractionalCentsWithoutDrift(): void
    {
        $party = $this->form->toParty(array_merge($this->validInput(), ['budget' => '19.99']));

        $this->assertSame(1999, $party->budgetCents());
    }

    public function testItParsesEveryAcceptedDateFormat(): void
    {
        $this->assertSame('2026-12-18 16:00:00', $this->form->parseDate('2026-12-18T16:00')->format('Y-m-d H:i:s'));
        $this->assertSame('2026-12-18 16:00:00', $this->form->parseDate('2026-12-18 16:00')->format('Y-m-d H:i:s'));
        $this->assertSame('2026-12-18 16:30:15', $this->form->parseDate('2026-12-18 16:30:15')->format('Y-m-d H:i:s'));
        $this->assertSame('2026-12-18 17:00:00', $this->form->parseDate('2026-12-18')->format('Y-m-d H:i:s'));
    }

    public function testItRefusesImpishDates(): void
    {
        $this->assertNull($this->form->parseDate(''));
        $this->assertNull($this->form->parseDate('18/12/2026'));
        $this->assertNull($this->form->parseDate('2026-13-45 99:99'));
    }
}
