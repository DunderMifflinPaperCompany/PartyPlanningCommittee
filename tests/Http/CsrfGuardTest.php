<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Tests\Http;

use PartyPlanningCommittee\Http\CsrfGuard;
use PHPUnit\Framework\TestCase;

/**
 * Belsnickel tests the guard at the door. Forged forms must never pass.
 */
final class CsrfGuardTest extends TestCase
{
    public function testItGeneratesALongRandomToken(): void
    {
        $guard = new CsrfGuard();
        $token = $guard->token();

        $this->assertSame(64, strlen($token));
        $this->assertSame($token, $guard->token());
    }

    public function testTwoGuardsDoNotShareAToken(): void
    {
        $this->assertNotSame((new CsrfGuard())->token(), (new CsrfGuard())->token());
    }

    public function testItReusesATokenFromStorage(): void
    {
        $guard = new CsrfGuard(['csrf_token' => 'a-previously-issued-token']);

        $this->assertSame('a-previously-issued-token', $guard->token());
        $this->assertTrue($guard->isValid('a-previously-issued-token'));
    }

    public function testItRejectsImpishTokens(): void
    {
        $guard = new CsrfGuard(['csrf_token' => 'honest-token']);

        $this->assertFalse($guard->isValid('forged-token'));
        $this->assertFalse($guard->isValid(''));
        $this->assertFalse($guard->isValid(null));
    }

    public function testItReplacesAnEmptyStoredToken(): void
    {
        $guard = new CsrfGuard(['csrf_token' => '']);

        $this->assertNotSame('', $guard->token());
        $this->assertArrayHasKey('csrf_token', $guard->storage());
    }
}
