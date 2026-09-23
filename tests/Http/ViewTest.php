<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Tests\Http;

use InvalidArgumentException;
use PartyPlanningCommittee\App;
use PartyPlanningCommittee\Http\View;
use PHPUnit\Framework\TestCase;

/**
 * Belsnickel tests the renderer. Unescaped output is the most impish sin of all.
 */
final class ViewTest extends TestCase
{
    private function view(): View
    {
        return new View(App::viewDirectory());
    }

    public function testItEscapesEveryDangerousCharacter(): void
    {
        $this->assertSame(
            '&lt;script&gt;alert(&#039;impish&#039;)&lt;/script&gt;',
            View::e("<script>alert('impish')</script>")
        );
        $this->assertSame('&quot;Belsnickel&quot;', View::e('"Belsnickel"'));
        $this->assertSame('', View::e(null));
    }

    public function testItRendersATemplate(): void
    {
        $html = $this->view()->render('errors/not_found', ['message' => 'No such party']);

        $this->assertStringContainsString('No such party', $html);
        $this->assertStringContainsString('Belsnickel is displeased', $html);
    }

    public function testItEscapesDataInsideTemplates(): void
    {
        $html = $this->view()->render('errors/not_found', ['message' => '<script>alert(1)</script>']);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testItWrapsATemplateInTheLayout(): void
    {
        $html = $this->view()->renderInLayout('errors/not_found', [
            'title' => 'Impish party',
            'message' => 'Gone',
            'offices' => [],
        ]);

        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('Impish party', $html);
        $this->assertStringContainsString('Gone', $html);
    }

    public function testItRefusesAnImpishTemplatePath(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->view()->render('../../etc/passwd');
    }

    public function testItRefusesAMissingTemplate(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->view()->render('parties/stamford');
    }

    public function testItLeavesNoOutputBufferBehind(): void
    {
        $level = ob_get_level();

        $this->view()->render('errors/not_found', ['message' => 'Gone']);

        $this->assertSame($level, ob_get_level());
    }
}
